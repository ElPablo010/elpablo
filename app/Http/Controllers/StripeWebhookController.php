<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Enums\OrderStatus;
use App\Mail\TicketOrderFailedMail;
use App\Models\PendingStripeSession;
use App\Models\Setting;
use App\Models\TicketOrder;
use App\Services\TicketOrderFulfillment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Ontvangt Stripe-webhooks (zonder CSRF — de Stripe-Signature-header is de
 * verificatie). checkout.session.completed rondt de bestelling af;
 * checkout.session.expired geeft de reservering meteen vrij. Bij een
 * fulfilment-fout antwoorden we bewust 500, zodat Stripe blijft retryen.
 */
class StripeWebhookController extends Controller
{
    public function handle(
        Request $request,
        PaymentGateway $gateway,
        TicketOrderFulfillment $fulfillment,
    ): JsonResponse {
        $secret = Setting::get('stripe_webhook_secret') ?: config('services.stripe.webhook_secret');
        if (! $secret) {
            Log::error('Stripe-webhook: geen webhook secret geconfigureerd.');

            return response()->json([], 500);
        }

        try {
            $event = $gateway->constructWebhookEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret,
            );
        } catch (\Throwable) {
            return response()->json([], 400);
        }

        $session = $event->data->object ?? null;
        $uuid = $session->client_reference_id ?? null;

        if ($event->type === 'checkout.session.expired') {
            $this->releaseExpired($uuid);

            return response()->json([]);
        }

        if ($event->type !== 'checkout.session.completed') {
            return response()->json([]);
        }

        if (! $uuid) {
            Log::warning('Stripe-webhook: checkout.session.completed zonder client_reference_id', [
                'stripe_session_id' => $session->id ?? null,
            ]);

            return response()->json([]);
        }

        $pending = PendingStripeSession::findByUuid($uuid);
        if (! $pending) {
            // Al verwerkt (bedankpagina was sneller) of onbekend — beide oké.
            return response()->json([]);
        }

        try {
            $fulfillment->complete($session, $uuid, $pending->payload);
        } catch (\Throwable $e) {
            Log::error('Stripe-webhook: fulfilment mislukt', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            // Max. één alarmmail per bestelling per 24u, ook al retryt Stripe vaker.
            if (Cache::add('ticket_fulfilment_failed_'.$uuid, true, now()->addDay())) {
                rescue(fn () => Mail::to(
                    Setting::get('admin_notification_email') ?: config('mail.from.address'),
                )->send(new TicketOrderFailedMail($uuid, $e->getMessage())));
            }

            return response()->json([], 500); // Stripe retryt.
        }

        return response()->json([]);
    }

    private function releaseExpired(?string $uuid): void
    {
        if (! $uuid) {
            return;
        }

        $pending = PendingStripeSession::findByUuid($uuid);
        $orderId = $pending?->payload['ticket_order_id'] ?? null;

        if ($orderId && ($order = TicketOrder::find($orderId)) && $order->status === OrderStatus::Pending) {
            $order->releaseReservation();
        }

        PendingStripeSession::forget($uuid);
    }
}
