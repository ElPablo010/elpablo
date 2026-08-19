<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Dunne laag rond de Kit v4-API (e-maillijst). Levert de formulier- en
 * tag-opties voor de dropdowns op Instellingen → E-mailmarketing, en het
 * client-object dat SubscribeTicketBuyerToKitJob gebruikt. Opties worden
 * kort gecachet zodat het openen van de instellingenpagina niet bij elke
 * Livewire-render de Kit-API aanspreekt.
 */
class KitApi
{
    private const CACHE_TTL_SECONDS = 300;

    /** De API-key in volgorde: expliciet meegegeven (formulierstate) → Setting → .env. */
    public static function apiKey(?string $override = null): ?string
    {
        $key = $override ?: Setting::get('kit_api_key') ?: config('services.kit.api_key');

        return blank($key) ? null : $key;
    }

    public static function client(string $apiKey): PendingRequest
    {
        return Http::baseUrl('https://api.kit.com/v4')
            ->withHeaders(['X-Kit-Api-Key' => $apiKey])
            ->acceptJson()
            ->timeout(15);
    }

    /** @return array<int|string, string> [id => naam], alfabetisch op naam. */
    public static function formOptions(?string $apiKeyOverride = null): array
    {
        return self::options('forms', $apiKeyOverride);
    }

    /** @return array<int|string, string> [id => naam], alfabetisch op naam. */
    public static function tagOptions(?string $apiKeyOverride = null): array
    {
        return self::options('tags', $apiKeyOverride);
    }

    /**
     * Maakt een tag aan in Kit en geeft het id terug. Bestaat de naam al
     * (Kit antwoordt dan 422), dan wordt het id van de bestaande tag
     * opgezocht zodat de keuze alsnog slaagt. Null bij falen.
     */
    public static function createTag(string $name, ?string $apiKeyOverride = null): ?int
    {
        if (! $apiKey = self::apiKey($apiKeyOverride)) {
            return null;
        }

        try {
            $response = self::client($apiKey)->post('/tags', ['name' => $name]);

            self::forgetCache('tags', $apiKey);

            if ($response->successful()) {
                return $response->json('tag.id');
            }

            // 422 = naam bestaat al; zoek het bestaande id op naam.
            $existing = array_search(trim($name), self::tagOptions($apiKey), true);

            return $existing === false ? null : (int) $existing;
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<int|string, string> */
    private static function options(string $resource, ?string $apiKeyOverride): array
    {
        if (! $apiKey = self::apiKey($apiKeyOverride)) {
            return [];
        }

        $cacheKey = self::cacheKey($resource, $apiKey);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $options = self::fetchAll($resource, $apiKey);
        } catch (Throwable) {
            // API onbereikbaar of key ongeldig: lege lijst, niet cachen,
            // zodat een gecorrigeerde key meteen opnieuw geprobeerd wordt.
            return [];
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);
        Cache::put($cacheKey, $options, self::CACHE_TTL_SECONDS);

        return $options;
    }

    /** @return array<int|string, string> */
    private static function fetchAll(string $resource, string $apiKey): array
    {
        $client = self::client($apiKey);
        $options = [];
        $cursor = null;

        // Cursor-paginering; de cap houdt een gek grote account binnen de perken.
        for ($page = 0; $page < 10; $page++) {
            $response = $client->get("/{$resource}", array_filter([
                'per_page' => 100,
                'after' => $cursor,
            ]))->throw();

            foreach ($response->json($resource, []) as $item) {
                $options[$item['id']] = $item['name'];
            }

            if (! $response->json('pagination.has_next_page')) {
                break;
            }
            $cursor = $response->json('pagination.end_cursor');
        }

        return $options;
    }

    private static function cacheKey(string $resource, string $apiKey): string
    {
        return "kit.{$resource}.".md5($apiKey);
    }

    private static function forgetCache(string $resource, string $apiKey): void
    {
        Cache::forget(self::cacheKey($resource, $apiKey));
    }
}
