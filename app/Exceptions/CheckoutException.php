<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Verwachte checkout-fout met een gebruikersgerichte (vertaalde) boodschap:
 * capaciteit intussen op, verkoop gesloten, totaal onder het Stripe-minimum…
 * De Livewire-component toont het bericht als validatiefout bij het formulier.
 */
class CheckoutException extends RuntimeException
{
}
