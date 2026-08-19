<?php

namespace App\Services\Translation\Concerns;

use Illuminate\Support\Str;

/**
 * Gedeeld gereedschap voor vertalers die een content-array (sectie-JSON,
 * instellingen-blob) doorlopen: vertaalbare tekst verzamelen, het resultaat op
 * hetzelfde pad terugzetten, en verwijzingen naar de vertaalde tegenhangers
 * laten wijzen. Gebruikt door PageTranslator en SettingsTranslator, zodat de
 * skip-lijst maar op één plek bestaat.
 */
trait TranslatesContentArrays
{
    /**
     * Sleutels waarvan de waarde nooit vertaald mag worden, waar ze ook in de
     * content-boom voorkomen.
     *
     * @var array<int, string>
     */
    private array $skipKeys = [
        // Verwijzingen en identificatie
        'page_id', 'event_ids', 'mixtape_ids', 'program_ids', 'media_id', 'media_ids',
        'section_id', 'section_type', 'form_type', 'link_type', 'id', 'type',
        'bookeo_account_id', 'key', 'slug',

        // Media en links
        'src', 'href', 'url', 'image_url', 'video_url', 'menu_href', 'icon',
        'upload', 'seo_image_url', 'canonical_url',

        // Vormgeving en layout
        'align', 'background', 'columns', 'columns_lg', 'layout', 'marker_style',
        'media_side', 'media_type', 'mode', 'shape', 'source', 'specs_display',
        'style', 'theme', 'third_type', 'variant', 'accent', 'badge_color',
        'text_align', 'image_position', 'position', 'height', 'padding',

        // Getallen en schakelaars
        'heading_level', 'max_visible', 'show_all', 'show_filters', 'new_tab', 'rating',
        'count', 'score', 'number', 'meta_robots',
    ];

    /**
     * Loop de content-boom af en verzamel elke vertaalbare string met zijn pad.
     *
     * @param  array<string, string>  $result
     */
    private function extract(mixed $data, array &$result, string $prefix = ''): void
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($key) && in_array($key, $this->skipKeys, true)) {
                    continue;
                }

                $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
                $this->extract($value, $result, $path);
            }

            return;
        }

        if (is_string($data) && $this->isTranslatable($data)) {
            $result[$prefix] = $data;
        }
    }

    /**
     * Bevat deze string eigenlijk wel tekst? Getallen, losse symbolen, URL's en
     * kleurcodes hoeven niet door de vertaler — dat scheelt tokens en sluit uit
     * dat een model er "creatief" mee omgaat.
     */
    private function isTranslatable(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || ! preg_match('/\p{L}{2,}/u', $value)) {
            return false;
        }

        return ! Str::startsWith($value, ['http://', 'https://', 'mailto:', 'tel:', '/', '#', 'data:']);
    }

    /**
     * Zet een waarde terug op zijn dot-pad in de content-array.
     */
    private function setAtPath(array &$data, string $path, mixed $value): void
    {
        $dot = strpos($path, '.');

        if ($dot === false) {
            $data[is_numeric($path) ? (int) $path : $path] = $value;

            return;
        }

        $key = substr($path, 0, $dot);
        $key = is_numeric($key) ? (int) $key : $key;

        if (! isset($data[$key]) || ! is_array($data[$key])) {
            $data[$key] = [];
        }

        $this->setAtPath($data[$key], substr($path, $dot + 1), $value);
    }
}
