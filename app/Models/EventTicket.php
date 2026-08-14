<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Eén rij = één fysiek ticket, geïdentificeerd door een ULID-token. Het token
 * is de QR-payload en de sleutel van de publieke statuspagina /t/{token}.
 */
#[Fillable([
    'event_id',
    'ticket_type_id',
    'ticket_order_id',
    'token',
    'status',
    'checked_in_at',
    'checked_in_by',
    'pdf_path',
])]
class EventTicket extends Model
{
    use HasFactory;

    /**
     * De app slaat UTC op; check-in-tijden tonen we in Belgische lokale tijd.
     */
    public const DISPLAY_TIMEZONE = 'Europe/Brussels';

    protected static function booted(): void
    {
        static::creating(function (EventTicket $ticket) {
            $ticket->token ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'checked_in_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function checkedInAtLocal(): ?Carbon
    {
        return $this->checked_in_at?->copy()->setTimezone(self::DISPLAY_TIMEZONE);
    }

    public function statusUrl(): string
    {
        return route('ticket.status', $this->token);
    }
}
