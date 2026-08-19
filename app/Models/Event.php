<?php

namespace App\Models;

use App\Enums\TicketDiscountType;
use App\Support\Locale;
use App\Support\Seo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'short_description',
    'description',
    'start_date',
    'end_date',
    'start_time',
    'end_time',
    'venue_name',
    'venue_address',
    'venue_postal_code',
    'venue_city',
    'image_url',
    'image_alt',
    'published',
    'cancelled_at',
    'cancellation_message',
    'meta_title',
    'meta_description',
])]
class Event extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'published' => 'boolean',
            'cancelled_at' => 'datetime',
        ];
    }

    /* -----------------------------------------------------------------
     |  Relaties
     | ----------------------------------------------------------------- */

    public function ticketTypes(): BelongsToMany
    {
        return $this->belongsToMany(TicketType::class, 'event_ticket_types')
            ->withPivot(['id', 'price', 'vat_rate', 'sales_end_date', 'capacity', 'sold_out', 'position'])
            ->withTimestamps()
            ->orderByPivot('position');
    }

    /**
     * De pivotrijen als eigen model — nodig om ze bij checkout met
     * lockForUpdate() te kunnen vergrendelen voor de capaciteitscheck.
     */
    public function eventTicketTypes(): HasMany
    {
        return $this->hasMany(EventTicketType::class)->orderBy('position');
    }

    public function ticketDiscounts(): HasMany
    {
        return $this->hasMany(EventTicketDiscount::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(TicketOrder::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(EventTranslation::class);
    }

    public function translationFor(string $locale): ?EventTranslation
    {
        return $this->translations->firstWhere('locale', $locale);
    }

    /* -----------------------------------------------------------------
     |  Status & scopes
     | ----------------------------------------------------------------- */

    /**
     * Of dit event afgelast is. Een afgelast event blijft zichtbaar op de
     * website — bezoekers die de aankondiging al zagen moeten kunnen vaststellen
     * dát het niet doorgaat — maar krijgt overal een duidelijke melding en de
     * ticketverkoop stopt.
     */
    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        // Een meerdaags event blijft "aankomend" tot zijn laatste dag voorbij is.
        return $query->where(fn (Builder $q) => $q
            ->whereDate('start_date', '>=', Carbon::today())
            ->orWhereDate('end_date', '>=', Carbon::today()));
    }

    /**
     * Of een tickettype vandaag nog online verkocht mag worden. De verkoop
     * blijft open t/m het einde van sales_end_date (de laatste verkoopdag).
     * Zonder waarde is er geen deadline en blijft de verkoop altijd open.
     */
    public static function ticketSalesOpenFor(?string $salesEndDate): bool
    {
        if (! $salesEndDate) {
            return true;
        }

        return Carbon::today()->lessThanOrEqualTo(Carbon::parse($salesEndDate)->startOfDay());
    }

    /* -----------------------------------------------------------------
     |  Prijzen & kortingen
     | ----------------------------------------------------------------- */

    /**
     * Alle automatische promo's die op een datum actief zijn voor een tickettype.
     *
     * @return \Illuminate\Support\Collection<int, EventTicketDiscount>
     */
    public function activeDiscountsFor(int $ticketTypeId, ?Carbon $onDate = null)
    {
        $date = ($onDate ?? Carbon::now())->toDateString();

        return $this->ticketDiscounts
            ->where('ticket_type_id', $ticketTypeId)
            ->filter(fn (EventTicketDiscount $d) => $d->valid_from->toDateString() <= $date
                && $d->valid_until->toDateString() >= $date)
            ->values();
    }

    /**
     * Prijs PER STUK voor weergave (kaarten, "vanaf"-prijzen). Houdt enkel
     * rekening met vaste-prijs-promo's — aantal-gebonden promo's (koop X + Y
     * gratis) kunnen niet in één stuksprijs uitgedrukt worden en horen thuis
     * in lineTotalFor().
     */
    public function currentPriceFor(int $ticketTypeId, ?Carbon $onDate = null): array
    {
        $ticketType = $this->ticketTypes->firstWhere('id', $ticketTypeId);
        $regular = $ticketType ? (float) $ticketType->pivot->price : 0.0;
        $vatRate = $ticketType ? (float) ($ticketType->pivot->vat_rate ?? 21) : 21.0;

        $active = $this->activeDiscountsFor($ticketTypeId, $onDate)
            ->where('type', TicketDiscountType::FixedPrice)
            ->sortBy(fn (EventTicketDiscount $d) => (float) $d->price)
            ->first();

        if (! $active) {
            return [
                'regular' => $regular,
                'current' => $regular,
                'vat_rate' => $vatRate,
                'discount' => null,
            ];
        }

        return [
            'regular' => $regular,
            'current' => (float) $active->price,
            'vat_rate' => $vatRate,
            'discount' => [
                'id' => $active->id,
                'name' => $active->name,
                'type' => $active->type->value,
                'valid_from' => $active->valid_from->format('Y-m-d'),
                'valid_until' => $active->valid_until->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Gezaghebbend regeltotaal voor een aantal tickets van één type, met de
     * actieve promo verrekend. Dit is de ENIGE bron van waarheid voor wat een
     * regel kost — de checkout-component én de checkout-service roepen dit aan.
     * De teruggegeven stuksprijs is het regeltotaal gelijkmatig over alle
     * tickets verdeeld (zo blijft "stuksprijs × aantal" exact kloppen). Bij
     * overlappende promo's wint het laagste regeltotaal — nooit stapelen.
     */
    public function lineTotalFor(int $ticketTypeId, int $quantity, ?Carbon $onDate = null): array
    {
        $quantity = max(0, $quantity);
        $ticketType = $this->ticketTypes->firstWhere('id', $ticketTypeId);
        $regular = $ticketType ? (float) $ticketType->pivot->price : 0.0;
        $vatRate = $ticketType ? (float) ($ticketType->pivot->vat_rate ?? 21) : 21.0;

        // Vertrekpunt: geen promo = reguliere prijs × aantal.
        $bestTotal = round($regular * $quantity, 2);
        $bestFree = 0;
        $bestDiscount = null;

        foreach ($this->activeDiscountsFor($ticketTypeId, $onDate) as $d) {
            if ($d->type === TicketDiscountType::BuyXGetY) {
                $buy = max(1, (int) $d->buy_quantity);
                $free = max(0, (int) $d->free_quantity);
                $group = $buy + $free;
                $freeCount = $group > 0 ? intdiv($quantity, $group) * $free : 0;
                $charged = max(0, $quantity - $freeCount);
                $lineTotal = round($regular * $charged, 2);
            } else { // fixed_price
                $freeCount = 0;
                $lineTotal = round((float) $d->price * $quantity, 2);
            }

            if ($lineTotal < $bestTotal) {
                $bestTotal = $lineTotal;
                $bestFree = $freeCount;
                $bestDiscount = [
                    'id' => $d->id,
                    'name' => $d->name,
                    'type' => $d->type->value,
                    'buy_quantity' => $d->buy_quantity,
                    'free_quantity' => $d->free_quantity,
                    'valid_from' => $d->valid_from->format('Y-m-d'),
                    'valid_until' => $d->valid_until->format('Y-m-d'),
                ];
            }
        }

        $unitIncVat = $quantity > 0 ? round($bestTotal / $quantity, 6) : 0.0;
        $unitExVat = $vatRate > 0 ? round($unitIncVat / (1 + $vatRate / 100), 6) : $unitIncVat;
        $totalExVat = $vatRate > 0 ? round($bestTotal / (1 + $vatRate / 100), 2) : $bestTotal;

        return [
            'quantity' => $quantity,
            'free' => $bestFree,
            'charged' => $quantity - $bestFree,
            'regular_unit_inc_vat' => $regular,
            'unit_inc_vat' => $unitIncVat,
            'unit_ex_vat' => $unitExVat,
            'vat_rate' => $vatRate,
            'total_inc_vat' => $bestTotal,
            'total_ex_vat' => $totalExVat,
            'discount' => $bestDiscount,
        ];
    }

    /* -----------------------------------------------------------------
     |  Vertalingen & URL's
     | ----------------------------------------------------------------- */

    /**
     * Een inhoudsveld in de gevraagde taal, met terugval op de NL-bron. Lege
     * vertaalvelden vallen per veld terug, zodat een half vertaald event nooit
     * lege tekst toont.
     */
    public function translated(string $field, ?string $locale = null): ?string
    {
        $locale ??= Locale::current();

        if ($locale === Locale::DEFAULT) {
            return $this->{$field};
        }

        $translation = $this->translationFor($locale);

        return filled($translation?->{$field}) ? $translation->{$field} : $this->{$field};
    }

    public function localizedPath(?string $locale = null): string
    {
        return Locale::href('/events/'.$this->slug, $locale);
    }

    public function publicUrl(?string $locale = null): string
    {
        return Seo::absoluteUrl($this->localizedPath($locale));
    }

    /* -----------------------------------------------------------------
     |  Weergave
     | ----------------------------------------------------------------- */

    public function startTimeFormatted(): ?string
    {
        return $this->start_time ? substr($this->start_time, 0, 5) : null;
    }

    public function endTimeFormatted(): ?string
    {
        return $this->end_time ? substr($this->end_time, 0, 5) : null;
    }

    /**
     * dd/mm/jjjj-weergave van de (eventueel meerdaagse) eventdatum.
     */
    public function dateLabel(): string
    {
        $start = $this->start_date->format('d/m/Y');

        if ($this->end_date && ! $this->end_date->isSameDay($this->start_date)) {
            return $start.' – '.$this->end_date->format('d/m/Y');
        }

        return $start;
    }
}
