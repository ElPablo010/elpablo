<?php

namespace Tests\Fakes;

use App\Contracts\PaymentGateway;
use RuntimeException;

/**
 * In-memory PaymentGateway voor tests: registreert de aangemaakte sessies,
 * geeft canned objecten terug en kan op commando falen. De webhook-verificatie
 * accepteert alleen de handtekening "valid-signature" en geeft gewoon de
 * ge-json-decodeerde payload terug — precies de vorm (->type, ->data->object)
 * die de controller verwacht.
 */
class FakePaymentGateway implements PaymentGateway
{
    /** @var array<int, array<string, mixed>> */
    public array $createdSessions = [];

    /** @var array<int, string> */
    public array $refunds = [];

    public bool $failOnCreate = false;

    public function createCheckoutSession(array $params): object
    {
        if ($this->failOnCreate) {
            throw new RuntimeException('Stripe is onbereikbaar (fake).');
        }

        $this->createdSessions[] = $params;
        $id = 'cs_test_'.count($this->createdSessions);

        return (object) [
            'id' => $id,
            'url' => 'https://checkout.stripe.test/'.$id,
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): object
    {
        foreach ($this->createdSessions as $index => $params) {
            if ('cs_test_'.($index + 1) === $sessionId) {
                return (object) [
                    'id' => $sessionId,
                    'payment_intent' => 'pi_fake_'.($index + 1),
                    'client_reference_id' => $params['client_reference_id'] ?? null,
                ];
            }
        }

        return (object) [
            'id' => $sessionId,
            'payment_intent' => 'pi_fake',
            'client_reference_id' => null,
        ];
    }

    public function constructWebhookEvent(string $payload, string $signature, string $secret): object
    {
        if ($signature !== 'valid-signature') {
            throw new \UnexpectedValueException('Ongeldige handtekening (fake).');
        }

        return json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
    }

    public function createRefund(string $paymentIntentId): object
    {
        $this->refunds[] = $paymentIntentId;

        return (object) ['id' => 're_fake', 'status' => 'succeeded'];
    }
}
