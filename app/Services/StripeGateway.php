<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\Setting;
use Stripe\StripeClient;
use Stripe\Webhook;

/**
 * Stripe-implementatie van de PaymentGateway. De secret komt primair uit de
 * database (Instellingen → Betalingen) zodat de klant hem zelf beheert; de
 * .env-waarde is de fallback voor CI/lokaal.
 */
class StripeGateway implements PaymentGateway
{
    public function createCheckoutSession(array $params): object
    {
        return $this->client()->checkout->sessions->create($params);
    }

    public function retrieveCheckoutSession(string $sessionId): object
    {
        return $this->client()->checkout->sessions->retrieve($sessionId);
    }

    public function constructWebhookEvent(string $payload, string $signature, string $secret): object
    {
        return Webhook::constructEvent($payload, $signature, $secret);
    }

    public function createRefund(string $paymentIntentId): object
    {
        return $this->client()->refunds->create(['payment_intent' => $paymentIntentId]);
    }

    private function client(): StripeClient
    {
        return new StripeClient((string) (Setting::get('stripe_secret') ?: config('services.stripe.secret')));
    }
}
