<?php

namespace App\Filament\Pages;

use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\EventTicket;
use App\Services\TicketScanner;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use UnitEnum;

/**
 * Scan-pagina voor check-in aan de deur: kies het event, richt de camera op de
 * QR-code van een ticket (html5-qrcode) of tik het token handmatig in. Toont
 * per scan een duidelijk groen/rood resultaat + live teller.
 */
class TicketScan extends Page
{
    protected static string|BackedEnum|null $navigationIcon = \Filament\Support\Icons\Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Events';

    protected static ?string $navigationLabel = 'Scannen';

    protected static ?string $title = 'Tickets scannen';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.ticket-scan';

    public ?int $eventId = null;

    /** @var array{status: string, message: string, name: ?string, type: ?string}|null */
    public ?array $lastResult = null;

    public function mount(): void
    {
        // Default: het eerstvolgende (of vandaag lopende) gepubliceerde event.
        $this->eventId = Event::query()
            ->published()
            ->upcoming()
            ->orderBy('start_date')
            ->value('id');
    }

    /** @return array<int, string> */
    public function getEventOptionsProperty(): array
    {
        return Event::query()
            ->published()
            ->whereDate('start_date', '>=', Carbon::today()->subDay())
            ->orderBy('start_date')
            ->pluck('name', 'id')
            ->all();
    }

    public function updatedEventId(): void
    {
        $this->lastResult = null;
    }

    public function checkIn(string $raw): void
    {
        if (! $this->eventId) {
            return;
        }

        $result = app(TicketScanner::class)->checkIn($raw, (int) $this->eventId, (int) auth()->id());

        $this->lastResult = [
            'status' => $result['status'],
            'message' => $result['message'],
            'name' => $result['ticket']?->order?->buyer_name,
            'type' => $result['ticket']?->ticketType?->name,
        ];
    }

    /** @return array{total: int, checked_in: int} */
    public function getStatsProperty(): array
    {
        if (! $this->eventId) {
            return ['total' => 0, 'checked_in' => 0];
        }

        $base = EventTicket::query()->where('event_id', $this->eventId);

        return [
            'total' => (clone $base)->whereIn('status', [TicketStatus::Paid, TicketStatus::CheckedIn])->count(),
            'checked_in' => (clone $base)->where('status', TicketStatus::CheckedIn)->count(),
        ];
    }
}
