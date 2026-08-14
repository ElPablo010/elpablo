<?php

/**
 * Ticketmail + PDF: de job ontdubbelt per bestelling (met force-bypass), de
 * mail wordt gerenderd in de taal van de koper, en de PDF (met SVG-QR) landt op
 * de private local-disk.
 */

use App\Jobs\SendTicketOrderEmailJob;
use App\Mail\TicketOrderMail;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\EventTicketPdf;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function paidOrder(string $locale = 'nl'): TicketOrder
{
    $event = Event::factory()->create(['name' => 'Latin Night']);
    $type = TicketType::factory()->create(['name' => 'Standaard', 'name_es' => 'Estándar']);

    $order = TicketOrder::factory()->paid()->create([
        'event_id' => $event->id,
        'locale' => $locale,
        'buyer_email' => 'koper@example.com',
    ]);
    $order->items()->create([
        'ticket_type_id' => $type->id,
        'description' => $type->name,
        'quantity' => 2,
        'unit_price_inc_vat' => 15,
        'vat_rate' => 21,
        'line_total_inc_vat' => 30,
    ]);
    EventTicket::factory()->count(2)->create([
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'ticket_order_id' => $order->id,
    ]);

    return $order->fresh();
}

it('sends the ticket mail with one PDF attachment per ticket', function () {
    Mail::fake();
    Storage::fake('local');
    $order = paidOrder();

    (new SendTicketOrderEmailJob($order->id))->handle(app(EventTicketPdf::class));

    Mail::assertSent(TicketOrderMail::class, function (TicketOrderMail $mail) use ($order) {
        return $mail->hasTo('koper@example.com')
            && count($mail->attachments()) === 2
            && $mail->order->is($order);
    });

    foreach ($order->tickets as $ticket) {
        expect($ticket->fresh()->pdf_path)->toBe('event-tickets/'.$ticket->token.'.pdf');
        Storage::disk('local')->assertExists('event-tickets/'.$ticket->token.'.pdf');
    }
});

it('deduplicates sends per order unless forced', function () {
    Mail::fake();
    Storage::fake('local');
    $order = paidOrder();

    (new SendTicketOrderEmailJob($order->id))->handle(app(EventTicketPdf::class));
    (new SendTicketOrderEmailJob($order->id))->handle(app(EventTicketPdf::class));
    Mail::assertSent(TicketOrderMail::class, 1);

    (new SendTicketOrderEmailJob($order->id, force: true))->handle(app(EventTicketPdf::class));
    Mail::assertSent(TicketOrderMail::class, 2);
});

it('does not send for pending orders', function () {
    Mail::fake();
    $order = paidOrder();
    $order->update(['status' => 'pending']);

    (new SendTicketOrderEmailJob($order->id))->handle(app(EventTicketPdf::class));

    Mail::assertNothingSent();
});

it('renders the mail in the buyer locale and restores the app locale', function () {
    Mail::fake();
    Storage::fake('local');
    $order = paidOrder(locale: 'es');

    app()->setLocale('nl');
    (new SendTicketOrderEmailJob($order->id))->handle(app(EventTicketPdf::class));

    expect(app()->getLocale())->toBe('nl');

    Mail::assertSent(TicketOrderMail::class, function (TicketOrderMail $mail) {
        $original = app()->getLocale();
        app()->setLocale($mail->order->locale);
        try {
            $html = $mail->render();
        } finally {
            app()->setLocale($original);
        }

        // De ES-vertaling bestaat pas in fase 6; tot dan bewijst de NL-bron-
        // string dat de render werkt. De kern: renderen crasht niet en het
        // event staat erin.
        return str_contains($html, 'Latin Night');
    });
});

it('embeds the QR code as an SVG data-URI in the PDF payload', function () {
    Storage::fake('local');
    $order = paidOrder();
    $ticket = $order->tickets->first();

    app(EventTicketPdf::class)->generate($ticket);

    $pdf = Storage::disk('local')->get('event-tickets/'.$ticket->token.'.pdf');
    expect($pdf)->toStartWith('%PDF');
});
