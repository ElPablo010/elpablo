<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\EventTicket;

/**
 * Vertaalt een gescande QR-payload naar een check-in-resultaat. Los van de
 * Filament-pagina zodat de logica op zichzelf testbaar is.
 *
 * Resultaten: ok / already (met tijdstip) / wrong_event / refunded / unpaid /
 * not_found.
 */
class TicketScanner
{
    /**
     * @return array{status: string, ticket: ?EventTicket, message: string}
     */
    public function checkIn(string $raw, int $eventId, int $byUserId): array
    {
        $token = $this->extractToken($raw);

        $ticket = $token !== ''
            ? EventTicket::query()->where('token', $token)->with(['ticketType', 'order'])->first()
            : null;

        if (! $ticket) {
            return $this->result('not_found', null, 'Onbekend ticket.');
        }

        if ($ticket->event_id !== $eventId) {
            return $this->result('wrong_event', $ticket, 'Dit ticket hoort bij een ander event.');
        }

        if ($ticket->status === TicketStatus::Refunded) {
            return $this->result('refunded', $ticket, 'Dit ticket werd terugbetaald.');
        }

        if ($ticket->status === TicketStatus::Reserved) {
            return $this->result('unpaid', $ticket, 'Dit ticket is nog niet betaald.');
        }

        if ($ticket->status === TicketStatus::CheckedIn) {
            return $this->result('already', $ticket, 'Al ingecheckt om '.$ticket->checkedInAtLocal()?->format('H:i').'.');
        }

        $ticket->update([
            'status' => TicketStatus::CheckedIn,
            'checked_in_at' => now(),
            'checked_in_by' => $byUserId,
        ]);

        return $this->result('ok', $ticket->fresh(), 'Welkom! Ticket is geldig.');
    }

    /**
     * Accepteert zowel een kale token als de volledige status-URL uit de
     * QR-code (https://…/t/{token}).
     */
    public function extractToken(string $raw): string
    {
        $raw = trim($raw);

        if (str_contains($raw, '/t/')) {
            $raw = rtrim(substr($raw, strrpos($raw, '/t/') + 3), '/');
        }

        return strtoupper(trim($raw));
    }

    private function result(string $status, ?EventTicket $ticket, string $message): array
    {
        return ['status' => $status, 'ticket' => $ticket, 'message' => $message];
    }
}
