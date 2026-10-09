<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\TicketDiscountType;
use App\Models\Event;
use App\Models\EventExtra;
use App\Models\EventTicketType;
use App\Models\TicketType;
use App\Support\EventResult;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Webgoeroe\Core\Filament\Schemas\Components\MediaPickerField;
use Webgoeroe\Core\Support\Locale;
use Webgoeroe\Core\Support\Seo;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Event')
                    ->persistTabInQueryString('tab')
                    ->tabs([
                        Tab::make('Basis')
                            ->id('basis')
                            ->schema(self::basisTab()),
                        Tab::make('Tickets')
                            ->id('tickets')
                            ->schema(self::ticketsTab()),
                        Tab::make('Promo\'s')
                            ->id('promos')
                            ->schema(self::promosTab()),
                        Tab::make('Extra\'s')
                            ->id('extras')
                            ->schema(self::extrasTab()),
                        Tab::make('Resultaat')
                            ->id('resultaat')
                            ->schema(self::resultTab()),
                        Tab::make('Vertalingen')
                            ->id('translations')
                            ->schema(self::translationsTab()),
                        Tab::make('SEO')
                            ->id('seo')
                            ->schema(self::seoTab()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function basisTab(): array
    {
        return [
            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('name')
                        ->label('Naam')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, callable $get, callable $set): void {
                            if (filled($state) && blank($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Wordt automatisch afgeleid van de naam. Gedeeld over alle talen.'),
                ]),
            Textarea::make('short_description')
                ->label('Korte omschrijving')
                ->rows(2)
                ->maxLength(500)
                ->helperText('Voor de eventkaarten in het overzicht.'),
            RichEditor::make('description')
                ->label('Omschrijving')
                ->extraInputAttributes(['style' => 'min-height: 28rem;']),
            Grid::make(['default' => 1, 'md' => 4])
                ->schema([
                    DatePicker::make('start_date')
                        ->label('Startdatum')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(),
                    DatePicker::make('end_date')
                        ->label('Einddatum')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->helperText('Enkel voor meerdaagse events.'),
                    TimePicker::make('start_time')
                        ->label('Startuur')
                        ->seconds(false),
                    TimePicker::make('end_time')
                        ->label('Einduur')
                        ->seconds(false),
                ]),
            Grid::make(['default' => 1, 'md' => 4])
                ->schema([
                    TextInput::make('venue_name')
                        ->label('Locatie')
                        ->maxLength(255),
                    TextInput::make('venue_address')
                        ->label('Adres')
                        ->maxLength(255),
                    TextInput::make('venue_postal_code')
                        ->label('Postcode')
                        ->maxLength(20),
                    TextInput::make('venue_city')
                        ->label('Stad')
                        ->maxLength(255),
                ]),
            TextInput::make('lineup')
                ->label('Line-up')
                ->maxLength(255)
                ->helperText('Wie er optreedt, gescheiden door komma\'s. Leeg = enkel '.Seo::brandName().'. Google toont dit bij het event en koppelt het aan de artiest.'),
            MediaPickerField::make('image_url', 'Afbeelding', required: false, helperText: 'Wordt getoond op de eventpagina en in het overzicht.'),
            TextInput::make('image_alt')
                ->label('Afbeelding — alt-tekst')
                ->maxLength(255),
            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    Toggle::make('published')
                        ->label('Gepubliceerd'),
                    Toggle::make('is_cancelled')
                        ->label('Afgelast')
                        ->live()
                        ->helperText('Het event blijft zichtbaar met een duidelijke melding; de ticketverkoop stopt.'),
                ]),
            Textarea::make('cancellation_message')
                ->label('Annuleringsboodschap')
                ->rows(2)
                ->visible(fn (callable $get): bool => (bool) $get('is_cancelled')),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function ticketsTab(): array
    {
        return [
            Repeater::make('eventTicketTypes')
                ->relationship()
                ->label('Tickettypes')
                ->addActionLabel('Tickettype toevoegen')
                ->orderColumn('position')
                ->reorderable()
                ->defaultItems(0)
                ->itemLabel(fn (array $state): ?string => TicketType::find($state['ticket_type_id'] ?? null)?->name)
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])
                        ->schema([
                            Select::make('ticket_type_id')
                                ->label('Tickettype')
                                ->options(fn (): array => TicketType::orderBy('name')->pluck('name', 'id')->all())
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (?int $state, callable $get, callable $set): void {
                                    $type = $state ? TicketType::find($state) : null;
                                    if ($type && blank($get('price'))) {
                                        $set('price', $type->default_price);
                                        $set('vat_rate', $type->default_vat_rate);
                                    }
                                })
                                ->helperText('Types beheer je onder Events → Tickettypes.'),
                            TextInput::make('price')
                                ->label('Prijs (incl. btw)')
                                ->numeric()
                                ->prefix('€')
                                ->required(),
                            TextInput::make('vat_rate')
                                ->label('Btw-tarief (%)')
                                ->numeric()
                                ->default(21)
                                ->required(),
                        ]),
                    Grid::make(['default' => 1, 'md' => 4])
                        ->schema([
                            DatePicker::make('sales_start_date')
                                ->label('Verkoop vanaf')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->helperText('Leeg = meteen te koop.'),
                            DatePicker::make('sales_end_date')
                                ->label('Verkoop t/m')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->after('sales_start_date')
                                ->helperText('Leeg = geen deadline.'),
                            TextInput::make('capacity')
                                ->label('Capaciteit')
                                ->numeric()
                                ->minValue(1)
                                ->helperText('Leeg = onbeperkt.'),
                            Toggle::make('sold_out')
                                ->label('Handmatig uitverkocht')
                                ->inline(false),
                        ]),
                    Placeholder::make('sold_count')
                        ->label('Verkocht')
                        ->content(fn (?EventTicketType $record): string => $record
                            ? $record->soldCount().($record->capacity !== null ? ' / '.$record->capacity : '')
                            : '—'),
                ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function promosTab(): array
    {
        return [
            Repeater::make('ticketDiscounts')
                ->relationship()
                ->label('Automatische promo\'s')
                ->addActionLabel('Promo toevoegen')
                ->defaultItems(0)
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])
                        ->schema([
                            TextInput::make('name')
                                ->label('Naam')
                                ->required()
                                ->maxLength(255)
                                ->helperText('bv. "Early bird".'),
                            Select::make('ticket_type_id')
                                ->label('Tickettype')
                                ->options(fn (): array => TicketType::orderBy('name')->pluck('name', 'id')->all())
                                ->required(),
                            Select::make('type')
                                ->label('Soort')
                                ->options(TicketDiscountType::class)
                                ->default(TicketDiscountType::FixedPrice)
                                ->required()
                                ->live(),
                        ]),
                    Grid::make(['default' => 1, 'md' => 3])
                        ->schema([
                            TextInput::make('price')
                                ->label('Promoprijs (incl. btw)')
                                ->numeric()
                                ->prefix('€')
                                ->visible(fn (callable $get): bool => $get('type') === TicketDiscountType::FixedPrice || $get('type') === TicketDiscountType::FixedPrice->value)
                                ->required(fn (callable $get): bool => $get('type') === TicketDiscountType::FixedPrice || $get('type') === TicketDiscountType::FixedPrice->value),
                            TextInput::make('buy_quantity')
                                ->label('Koop-aantal')
                                ->numeric()
                                ->minValue(1)
                                ->visible(fn (callable $get): bool => $get('type') === TicketDiscountType::BuyXGetY || $get('type') === TicketDiscountType::BuyXGetY->value)
                                ->required(fn (callable $get): bool => $get('type') === TicketDiscountType::BuyXGetY || $get('type') === TicketDiscountType::BuyXGetY->value),
                            TextInput::make('free_quantity')
                                ->label('Gratis-aantal')
                                ->numeric()
                                ->minValue(1)
                                ->visible(fn (callable $get): bool => $get('type') === TicketDiscountType::BuyXGetY || $get('type') === TicketDiscountType::BuyXGetY->value)
                                ->required(fn (callable $get): bool => $get('type') === TicketDiscountType::BuyXGetY || $get('type') === TicketDiscountType::BuyXGetY->value),
                        ]),
                    Grid::make(['default' => 1, 'md' => 2])
                        ->schema([
                            DatePicker::make('valid_from')
                                ->label('Geldig van')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required(),
                            DatePicker::make('valid_until')
                                ->label('Geldig t/m')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required()
                                ->afterOrEqual('valid_from'),
                        ]),
                ]),
        ];
    }

    /**
     * Extra's: alles wat je bij een bestelling kunt kiezen zonder dat het een
     * ticket is (een gratis groepstafel, later een drankkaart). Ze tellen nooit
     * mee als bezoeker en krijgen geen QR-ticket.
     *
     * @return array<int, mixed>
     */
    private static function extrasTab(): array
    {
        return [
            Repeater::make('extras')
                ->relationship()
                ->label('Extra\'s')
                ->addActionLabel('Extra toevoegen')
                ->orderColumn('position')
                ->reorderable()
                ->defaultItems(0)
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])
                        ->schema([
                            TextInput::make('name')
                                ->label('Naam')
                                ->required()
                                ->maxLength(255)
                                ->helperText('bv. "Groepstafel".'),
                            TextInput::make('price')
                                ->label('Prijs (incl. btw)')
                                ->numeric()
                                ->prefix('€')
                                ->default(0)
                                ->required()
                                ->helperText('0 = gratis.'),
                            TextInput::make('vat_rate')
                                ->label('Btw-tarief (%)')
                                ->numeric()
                                ->default(21)
                                ->required(),
                        ]),
                    Textarea::make('description')
                        ->label('Omschrijving')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Eén zin onder de naam in de checkout.'),
                    Grid::make(['default' => 1, 'md' => 3])
                        ->schema([
                            TextInput::make('capacity')
                                ->label('Voorraad')
                                ->numeric()
                                ->minValue(1)
                                ->helperText('bv. 8 tafels. Leeg = onbeperkt.'),
                            TextInput::make('max_per_order')
                                ->label('Max. per bestelling')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required(),
                            Toggle::make('sold_out')
                                ->label('Handmatig volzet')
                                ->inline(false),
                        ]),
                    Grid::make(['default' => 1, 'md' => 2])
                        ->schema([
                            TextInput::make('min_tickets')
                                ->label('Beschikbaar vanaf … tickets')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->required()
                                ->helperText('0 = altijd beschikbaar. Bij 12 verschijnt de extra pas vanaf 12 tickets.'),
                            Select::make('ticket_type_id')
                                ->label('Tel enkel dit tickettype')
                                ->options(fn (): array => TicketType::orderBy('name')->pluck('name', 'id')->all())
                                ->placeholder('Alle tickets samen')
                                ->helperText('Leeg = alle tickets in de bestelling tellen mee voor de drempel.'),
                        ]),
                    Fieldset::make('Vertalingen')
                        ->columns(2)
                        ->schema(self::extraTranslationFields()),
                    Placeholder::make('claimed_count')
                        ->label('Geclaimd')
                        ->content(fn (?EventExtra $record): string => $record
                            ? $record->claimedCount().($record->capacity !== null ? ' / '.$record->capacity : '')
                            : '—'),
                ]),
        ];
    }

    /**
     * Resultaat van het event: kosten en opbrengsten buiten de online
     * ticketverkoop, telkens omschrijving + bedrag EXCL. btw. De online tickets
     * komen automatisch uit de betaalde bestellingen (EventResult). De
     * samenvatting rekent live mee met wat je in de repeaters intikt.
     *
     * @return array<int, mixed>
     */
    private static function resultTab(): array
    {
        return [
            Placeholder::make('result_summary')
                ->hiddenLabel()
                ->content(fn (?Event $record, callable $get): HtmlString => self::resultSummary($record, $get)),
            Repeater::make('costs')
                ->relationship()
                ->label('Kosten (excl. btw)')
                ->addActionLabel('Kost toevoegen')
                ->orderColumn('position')
                ->reorderable()
                ->defaultItems(0)
                ->live()
                ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                ->schema(self::amountLineFields('bv. "DJ Carlos", "Licht & geluid", "Affiches", "Facebook-advertenties".')),
            Repeater::make('revenues')
                ->relationship()
                ->label('Andere opbrengsten (excl. btw)')
                ->addActionLabel('Opbrengst toevoegen')
                ->orderColumn('position')
                ->reorderable()
                ->defaultItems(0)
                ->live()
                ->itemLabel(fn (array $state): ?string => $state['description'] ?? null)
                ->schema(self::amountLineFields('bv. "Kassa", "Bar", "Sponsoring". De online tickets tellen automatisch mee.')),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function amountLineFields(string $hint): array
    {
        return [
            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    TextInput::make('description')
                        ->label('Omschrijving')
                        ->required()
                        ->maxLength(255)
                        ->helperText($hint)
                        ->columnSpan(['md' => 2]),
                    TextInput::make('amount')
                        ->label('Bedrag (excl. btw)')
                        ->numeric()
                        ->prefix('€')
                        ->required()
                        ->live(onBlur: true),
                ]),
        ];
    }

    private static function resultSummary(?Event $record, callable $get): HtmlString
    {
        $sum = fn (?array $lines): float => round((float) collect($lines ?? [])
            ->sum(fn (array $line): float => is_numeric($line['amount'] ?? null) ? (float) $line['amount'] : 0.0), 2);

        // Tickets en bezoekers uit de database; kosten en andere opbrengsten uit
        // het formulier, zodat de samenvatting meteen klopt bij het intikken.
        $saved = $record?->exists ? EventResult::for($record) : null;

        $result = new EventResult(
            ticketRevenue: $saved?->ticketRevenue ?? 0.0,
            otherRevenue: $sum($get('revenues')),
            costs: $sum($get('costs')),
            visitors: $saved?->visitors ?? 0,
        );

        $margin = $result->margin();
        $breakEven = $result->breakEvenTickets();

        $tiles = [
            ['Online tickets', EventResult::money($result->ticketRevenue), null],
            ['Andere opbrengsten', EventResult::money($result->otherRevenue), null],
            ['Kosten', EventResult::money($result->costs), null],
            ['Resultaat', EventResult::money($result->result()), $result->result() < 0 ? '#dc2626' : '#16a34a'],
            ['Marge', $margin === null ? '—' : number_format($margin, 1, ',', '.').' %', null],
            ['Bezoekers', (string) $result->visitors, null],
            ['Resultaat per bezoeker', EventResult::money($result->resultPerVisitor()), null],
            ['Break-even', $breakEven === null ? '—' : $breakEven.' tickets', null],
        ];

        $html = '<div style="display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));">';
        foreach ($tiles as [$label, $value, $color]) {
            $html .= '<div style="border: 1px solid rgba(120,120,120,.25); border-radius: .75rem; padding: .75rem 1rem;">'
                .'<div style="font-size: .8rem; opacity: .7;">'.e($label).'</div>'
                .'<div style="font-size: 1.25rem; font-weight: 700;'.($color ? ' color: '.$color.';' : '').'">'.e($value).'</div>'
                .'</div>';
        }
        $html .= '</div>';
        $html .= '<p style="font-size: .8rem; opacity: .7; margin-top: .5rem;">Alle bedragen excl. btw. Online tickets = betaalde bestellingen (terugbetaald telt niet mee, kortingscodes zijn verrekend). Break-even = tickets nodig aan de gemiddelde ticketprijs.</p>';

        return new HtmlString($html);
    }

    /**
     * Naam en omschrijving per extra taal — lege velden vallen terug op NL.
     *
     * @return array<int, mixed>
     */
    private static function extraTranslationFields(): array
    {
        $fields = [];

        foreach (Locale::supported() as $locale) {
            if ($locale === Locale::defaultLocale()) {
                continue;
            }

            $label = Locale::labels()[$locale] ?? strtoupper($locale);

            $fields[] = TextInput::make("name_{$locale}")
                ->label("Naam ({$label})")
                ->maxLength(255);
            $fields[] = Textarea::make("description_{$locale}")
                ->label("Omschrijving ({$label})")
                ->rows(2)
                ->maxLength(500);
        }

        return $fields;
    }

    /**
     * De vertaaltabs schrijven naar statePath translations.{locale}; het laden
     * en bewaren gebeurt in de trait ManagesEventFormData op de pagina's.
     *
     * @return array<int, mixed>
     */
    private static function translationsTab(): array
    {
        $fieldsets = [];

        foreach (Locale::supported() as $locale) {
            if ($locale === Locale::defaultLocale()) {
                continue;
            }

            $fieldsets[] = Fieldset::make(Locale::labels()[$locale] ?? strtoupper($locale))
                ->statePath("translations.{$locale}")
                ->columns(1)
                ->schema([
                    TextInput::make('name')
                        ->label('Naam')
                        ->maxLength(255),
                    Textarea::make('short_description')
                        ->label('Korte omschrijving')
                        ->rows(2)
                        ->maxLength(500),
                    RichEditor::make('description')
                        ->label('Omschrijving')
                        ->extraInputAttributes(['style' => 'min-height: 28rem;']),
                ]);
        }

        return [
            Placeholder::make('translations_hint')
                ->hiddenLabel()
                ->content('Lege velden vallen op de publieke site terug op de Nederlandse tekst.'),
            ...$fieldsets,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function seoTab(): array
    {
        return [
            TextInput::make('meta_title')
                ->label('Meta-titel')
                ->maxLength(60)
                ->helperText('Ideaal ~60 tekens. Leeg = de eventnaam.'),
            Textarea::make('meta_description')
                ->label('Meta-omschrijving')
                ->rows(3)
                ->maxLength(160)
                ->helperText('Ideaal ~160 tekens. Leeg = de korte omschrijving.'),
        ];
    }
}
