<?php

namespace App\Contracts;

/**
 * Dunne abstractie over de betaalprovider (Stripe), zodat tests een fake
 * kunnen binden en de rest van de app nooit rechtstreeks aan het SDK hangt.
 * Retourwaarden zijn losse objecten met de velden die wij gebruiken
 * (->id, ->url, ->payment_intent, ->client_reference_id, ->type, ->data).
 */
interface PaymentGateway
{
    /** Maak een Stripe Checkout-sessie; geeft een object met ->id en ->url. */
    public function createCheckoutSession(array $params): object;

    public function retrieveCheckoutSession(string $sessionId): object;

    /**
     * Verifieer en parse een webhook-payload; gooit bij een ongeldige
     * handtekening. Geeft een object met ->type en ->data->object.
     */
    public function constructWebhookEvent(string $payload, string $signature, string $secret): object;

    /** Volledige terugbetaling van een payment intent. */
    public function createRefund(string $paymentIntentId): object;
}
