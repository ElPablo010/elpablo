<?php

/**
 * Check-in: alle scanner-resultaten, token-extractie uit een volledige URL,
 * de omkeerbare handmatige toggle in de bestelling, en de scan-pagina zelf.
 */

use App\Enums\TicketStatus;
use App\Filament\Pages\TicketScan;
use App\Models\Event;
use App\Models\EventTicket;
use App\Models\TicketOrder;
use App\Models\TicketType;
use App\Services\TicketScanner;
use Livewire\Livewire;

function scannableTicket(array $attributes = []): EventTicket
{
    $event = Event::factory()->create();
    $type = TicketType::factory()->create(['name' => 'Standaard']);
    $order = TicketOrder::factory()->paid()->create(['event_id' => $event->id, 'buyer_name' => 'Test Koper']);

    return EventTicket::factory()->create($attributes + [
        'event_id' => $event->id,
        'ticket_type_id' => $type->id,
        'ticket_order_id' => $order->id,
        'status' => TicketStatus::Paid,
    ]);
}

it('checks in a valid paid ticket exactly once', function () {
    $ticket = scannableTicket();
    $scanner = app(TicketScanner::class);
    $adminId = admin()->id;

    $first = $scanner->checkIn($ticket->token, $ticket->event_id, $adminId);
    expect($first['status'])->toBe('ok');

    $ticket = $ticket->fresh();
    expect($ticket->status)->toBe(TicketStatus::CheckedIn)
        ->and($ticket->checked_in_at)->not->toBeNull()
        ->and($ticket->checked_in_by)->toBe($adminId);

    $second = $scanner->checkIn($ticket->token, $ticket->event_id, $adminId);
    expect($second['status'])->toBe('already')
        ->and($second['message'])->toContain('Al ingecheckt om');
});

it('accepts the full status URL from the QR code', function () {
    $ticket = scannableTicket();

    $result = app(TicketScanner::class)->checkIn($ticket->statusUrl(), $ticket->event_id, admin()->id);

    expect($result['status'])->toBe('ok');
});

it('rejects unknown, wrong-event, refunded and unpaid tickets', function () {
    $scanner = app(TicketScanner::class);
    $adminId = admin()->id;

    expect($scanner->checkIn('BESTAAT-NIET', 1, $adminId)['status'])->toBe('not_found');

    $ticket = scannableTicket();
    $other = Event::factory()->create();
    expect($scanner->checkIn($ticket->token, $other->id, $adminId)['status'])->toBe('wrong_event');

    $refunded = scannableTicket(['status' => TicketStatus::Refunded]);
    expect($scanner->checkIn($refunded->token, $refunded->event_id, $adminId)['status'])->toBe('refunded');

    $reserved = scannableTicket(['status' => TicketStatus::Reserved]);
    expect($scanner->checkIn($reserved->token, $reserved->event_id, $adminId)['status'])->toBe('unpaid');

    // Geweigerde scans veranderen niets aan het ticket.
    expect($refunded->fresh()->status)->toBe(TicketStatus::Refunded)
        ->and($reserved->fresh()->status)->toBe(TicketStatus::Reserved);
});

it('runs the scan flow through the Filament page with live stats', function () {
    $ticket = scannableTicket();
    $this->actingAs(admin());

    Livewire::test(TicketScan::class)
        ->set('eventId', $ticket->event_id)
        ->call('checkIn', $ticket->token)
        ->assertSet('lastResult.status', 'ok')
        ->assertSet('lastResult.name', 'Test Koper')
        ->assertSee('1 / 1');
});

it('toggles a manual check-in reversibly from the order view', function () {
    $ticket = scannableTicket();
    $this->actingAs(admin());

    Livewire::test(\App\Filament\Resources\TicketOrders\RelationManagers\TicketsRelationManager::class, [
        'ownerRecord' => $ticket->order,
        'pageClass' => \App\Filament\Resources\TicketOrders\Pages\ViewTicketOrder::class,
    ])
        ->callTableAction('toggleCheckIn', $ticket)
        ->assertHasNoTableActionErrors();

    expect($ticket->fresh()->status)->toBe(TicketStatus::CheckedIn);

    Livewire::test(\App\Filament\Resources\TicketOrders\RelationManagers\TicketsRelationManager::class, [
        'ownerRecord' => $ticket->order,
        'pageClass' => \App\Filament\Resources\TicketOrders\Pages\ViewTicketOrder::class,
    ])
        ->callTableAction('toggleCheckIn', $ticket)
        ->assertHasNoTableActionErrors();

    $ticket = $ticket->fresh();
    expect($ticket->status)->toBe(TicketStatus::Paid)
        ->and($ticket->checked_in_at)->toBeNull();
});
