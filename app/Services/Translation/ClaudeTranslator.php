<?php

namespace App\Services\Translation;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * De laag die met Claude praat. Krijgt een platte [sleutel => tekst]-map binnen
 * en geeft dezelfde sleutels terug met vertaalde teksten.
 *
 * Twee keuzes die de rest van de pijplijn eenvoudig houden:
 *
 * 1. **Structured outputs.** We vragen het antwoord als een JSON-schema
 *    (een lijst {key, text}-paren) in plaats van "geef enkel JSON terug". Het
 *    model kán dan geen codeblok-fences of uitleg meesturen, dus er is geen
 *    fragiele opschoon-stap en geen parse-retry nodig.
 * 2. **Chunking.** Grote pagina's gaan in stukken naar de API, zodat een lange
 *    pagina nooit tegen de output-limiet aanloopt en halverwege afkapt.
 *
 * Sleutels blijven ongemoeid: zij zijn het adres waar de vertaling straks weer
 * teruggeschreven wordt (zie PageTranslator::setAtPath()).
 */
class ClaudeTranslator
{
    /**
     * Ruwe tekens per API-call. Bewust laag: het antwoord is ongeveer even
     * lang als de invoer, en beide moeten samen ruim binnen max_tokens passen.
     */
    private const CHUNK_CHARACTERS = 12000;

    /**
     * Vertaal een set teksten. De sleutels van de teruggegeven array zijn
     * exact die van de invoer; teksten die het model niet teruggaf blijven weg,
     * zodat de aanroeper zelf kan beslissen wat er met een gat gebeurt.
     *
     * @param  array<string, string>  $texts
     * @return array<string, string>
     */
    public function translate(array $texts, string $fromLocale, string $toLocale, ?string $context = null): array
    {
        $texts = array_filter($texts, fn ($text) => is_string($text) && trim($text) !== '');

        if ($texts === []) {
            return [];
        }

        $translated = [];

        foreach ($this->chunk($texts) as $chunk) {
            $translated += $this->callClaude($chunk, $fromLocale, $toLocale, $context);
        }

        return $translated;
    }

    /**
     * Splits de teksten in porties die elk binnen één API-call passen. Eén
     * tekst die op zichzelf al te groot is, krijgt gewoon zijn eigen call —
     * afkappen zou stille inhoudsverlies betekenen.
     *
     * @param  array<string, string>  $texts
     * @return array<int, array<string, string>>
     */
    private function chunk(array $texts): array
    {
        $chunks = [];
        $current = [];
        $size = 0;

        foreach ($texts as $key => $text) {
            $length = mb_strlen($text) + mb_strlen((string) $key);

            if ($current !== [] && $size + $length > self::CHUNK_CHARACTERS) {
                $chunks[] = $current;
                $current = [];
                $size = 0;
            }

            $current[$key] = $text;
            $size += $length;
        }

        if ($current !== []) {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /**
     * @param  array<string, string>  $texts
     * @return array<string, string>
     */
    private function callClaude(array $texts, string $fromLocale, string $toLocale, ?string $context): array
    {
        // Zelfde sleutel-bron als de SEO-adviseur: Instellingen → Algemeen
        // in de admin wint; ANTHROPIC_API_KEY in .env is de terugval.
        $apiKey = Setting::get('anthropic_api_key') ?: config('services.anthropic.api_key');

        if (blank($apiKey)) {
            throw TranslationException::missingApiKey();
        }

        $payload = [];
        foreach ($texts as $key => $text) {
            $payload[] = ['key' => (string) $key, 'text' => $text];
        }

        // Het "sleurwerk"-model: korte strings omzetten, waar niets te bedenken
        // valt. Zie config/services.php — nooit hier hardcoden.
        $model = config('services.anthropic.models.bulk', 'claude-haiku-4-5-20251001');

        $outputConfig = [
            'format' => [
                'type' => 'json_schema',
                'schema' => $this->responseSchema(),
            ],
        ];

        // `effort` bestaat niet op elk model: Haiku weigert de héle request met
        // een 400 als je het meestuurt. Voor Haiku is het bovendien zinloos —
        // dat model denkt niet adaptief, dus er valt geen denkwerk te temperen.
        // Op de grotere modellen houdt 'low' de kosten en de wachttijd laag
        // zonder dat de kwaliteit merkbaar zakt.
        if ($this->supportsEffort($model)) {
            $outputConfig['effort'] = 'low';
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(180)
            ->retry(2, 2000, throw: false)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 16000,
                'output_config' => $outputConfig,
                'system' => $this->systemPrompt($fromLocale, $toLocale, $context),
                'messages' => [[
                    'role' => 'user',
                    'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]],
            ]);

        if (! $response->successful()) {
            throw TranslationException::fromResponse($response);
        }

        $decoded = json_decode((string) $this->firstTextBlock($response->json('content', [])), true);

        if (! is_array($decoded) || ! isset($decoded['translations']) || ! is_array($decoded['translations'])) {
            throw TranslationException::invalidResponse();
        }

        $result = [];

        foreach ($decoded['translations'] as $entry) {
            $key = $entry['key'] ?? null;
            $text = $entry['text'] ?? null;

            // Enkel sleutels die we zelf meestuurden overnemen — zo kan een
            // afwijkend antwoord nooit vreemde velden in de content injecteren.
            if (is_string($key) && is_string($text) && array_key_exists($key, $texts)) {
                $result[$key] = $text;
            }
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'translations' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'key' => ['type' => 'string'],
                            'text' => ['type' => 'string'],
                        ],
                        'required' => ['key', 'text'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['translations'],
            'additionalProperties' => false,
        ];
    }

    private function systemPrompt(string $fromLocale, string $toLocale, ?string $context): string
    {
        $from = $this->languageName($fromLocale);
        $to = $this->languageName($toLocale);

        $lines = [
            'You translate website copy for Ark van Noë, a family leisure resort in Lichtaart (Kasterlee, Belgium) with a brasserie and beach bar, canoe rental, glamping, a playground and a petting farm.',
            "Translate every `text` value from {$from} to {$to} and return one entry per input entry, with the `key` copied over unchanged.",
            '',
            'Rules:',
            '- Never translate, reorder or invent keys. Return exactly the keys you were given.',
            '- Keep any HTML markup intact: translate the text between tags, never the tags, attributes or URLs.',
            '- Leave URLs, file paths, e-mail addresses, phone numbers, hex colours and prices unchanged.',
            '- Keep the brand name "Ark van Noë" and Belgian place names (Lichtaart, Kasterlee, Kempen, Herentals, Grobbendonk) as they are.',
            '- Match the register of the source: warm, direct and welcoming, never stiff or corporate.',
            '- Keep the length close to the original; these strings sit in a fixed layout.',
            '- Dates stay day-first (10/08/2026), never month-first.',
            '- Do not include internal or system XML tags in your response.',
        ];

        if (filled($context)) {
            $lines[] = '';
            $lines[] = 'Context for this batch: '.$context;
        }

        return implode("\n", $lines);
    }

    /**
     * Kent dit model de `effort`-parameter? Haiku niet — die geeft dan een
     * HTTP 400 op de hele request, dus dit moet vóór het versturen gecheckt
     * worden en niet achteraf opgevangen.
     */
    private function supportsEffort(string $model): bool
    {
        return ! str_starts_with($model, 'claude-haiku-');
    }

    /**
     * Het eerste tekstblok uit een Messages-API-antwoord.
     *
     * Niet `content[0]` pakken: denkt het model bij deze prompt hardop, dan is
     * blok 0 een thinking-blok zonder `text`, en dan faalt de vertaling op
     * "ongeldig antwoord" terwijl het antwoord prima was — wij lazen het
     * verkeerd. Of er gedacht wordt hangt af van de prompt, niet van het
     * model, dus dit is een grillige fout die je niet met één test uitsluit.
     *
     * @param  array<int, array<string, mixed>>  $content
     */
    private function firstTextBlock(array $content): ?string
    {
        foreach ($content as $block) {
            if (($block['type'] ?? null) === 'text' && filled($block['text'] ?? null)) {
                return trim($block['text']);
            }
        }

        return null;
    }

    private function languageName(string $locale): string
    {
        return match ($locale) {
            'nl' => 'Dutch',
            'en' => 'English',
            'es' => 'Spanish',
            'fr' => 'French',
            'de' => 'German',
            default => strtoupper($locale),
        };
    }
}
