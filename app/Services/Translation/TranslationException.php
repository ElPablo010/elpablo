<?php

namespace App\Services\Translation;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Fouten uit de vertaalpijplijn, met een boodschap die rechtstreeks aan de
 * klant getoond mag worden. Een stille catch is hier bewust niet de bedoeling:
 * een mislukte vertaling moet zichtbaar zijn, niet half doorgaan.
 */
class TranslationException extends RuntimeException
{
    public static function missingApiKey(): self
    {
        return new self(
            'Er is geen Anthropic API-sleutel ingesteld. Vul die in onder Instellingen → Integraties.',
        );
    }

    public static function fromResponse(Response $response): self
    {
        $message = $response->json('error.message') ?: $response->body();

        return new self("De vertaaldienst gaf een fout ({$response->status()}): {$message}");
    }

    public static function invalidResponse(): self
    {
        return new self('De vertaaldienst gaf een onverwacht antwoord terug. Probeer het opnieuw.');
    }
}
