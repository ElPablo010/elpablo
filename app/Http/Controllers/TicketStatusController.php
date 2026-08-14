<?php

namespace App\Http\Controllers;

use App\Models\EventTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Publieke statuspagina per ticket — de QR-code op het ticket wijst hierheen.
 * Toont enkel status en eventgegevens; inchecken zelf gebeurt uitsluitend via
 * de scan-pagina in de admin.
 */
class TicketStatusController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $ticket = EventTicket::query()
            ->where('token', $token)
            ->with(['event', 'ticketType', 'order'])
            ->first();

        return response()->view('pages.ticket-status', [
            'ticket' => $ticket,
        ], $ticket === null ? 404 : 200);
    }
}
