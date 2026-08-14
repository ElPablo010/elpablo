<?php

namespace App\Services;

use App\Models\EventTicket;
use App\Support\SiteHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EventTicketPdf
{
    /**
     * Render één ticket naar een PDF (met QR-code), bewaar het op de private
     * "local" disk en geef het opslagpad terug. Hergebruikt het bestaande
     * bestand wanneer dat al bestaat.
     */
    public function generate(EventTicket $ticket): string
    {
        if ($ticket->pdf_path && Storage::disk('local')->exists($ticket->pdf_path)) {
            return $ticket->pdf_path;
        }

        $ticket->loadMissing(['event', 'ticketType', 'order']);

        $pdf = Pdf::loadView('pdf.event-ticket', [
            'ticket' => $ticket,
            'event' => $ticket->event,
            'logoData' => $this->logoData(),
            'qrData' => $this->qrData($ticket),
        ])->setPaper('a4');

        $path = 'event-tickets/'.$ticket->token.'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        $ticket->forceFill(['pdf_path' => $path])->save();

        return $path;
    }

    /**
     * QR-code als base64 SVG data-URI. De QR bevat de publieke status-URL van
     * het ticket, zodat elke camera hem als geldige link herkent. SVG (i.p.v.
     * PNG) vermijdt de imagick-afhankelijkheid op Combell shared hosting.
     */
    private function qrData(EventTicket $ticket): string
    {
        $svg = QrCode::format('svg')
            ->size(220)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($ticket->statusUrl());

        return 'data:image/svg+xml;base64,'.base64_encode((string) $svg);
    }

    /**
     * dompdf kan geen remote URLs ophalen — embed het sitelogo als base64
     * data-URI. Het logo-veld (Website → Header) is een root-relatieve URL
     * naar een bestand in public/.
     */
    private function logoData(): ?string
    {
        $logoUrl = SiteHeader::current()['logo'] ?? null;
        if (blank($logoUrl)) {
            return null;
        }

        $path = public_path(ltrim((string) parse_url($logoUrl, PHP_URL_PATH), '/'));
        if (! is_file($path)) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
