<?php

namespace App\Support;

/**
 * Zet een geplakte YouTube/Vimeo-URL om naar zijn iframe-embed-URL. Beheerders
 * plakken wat ze in de adresbalk zien (watch?v=…, youtu.be/…, shorts/…), maar
 * zo'n pagina-URL speelt niet af in een <video>- of iframe-element — vandaar
 * deze vertaalslag. Directe videobestanden (mp4 e.d.) geven null terug en
 * blijven via de native <video>-speler lopen.
 *
 * YouTube gaat via youtube-nocookie.com (privacy-enhanced mode): geen
 * tracking-cookies vóór het afspelen, zodat de embed geen consent uit de
 * cookiebanner nodig heeft.
 */
class VideoEmbed
{
    public static function url(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (in_array($host, ['youtube.com', 'youtube-nocookie.com', 'm.youtube.com'], true)) {
            $id = match (true) {
                str_starts_with($path, 'watch') => $query['v'] ?? null,
                str_starts_with($path, 'shorts/') => substr($path, strlen('shorts/')),
                str_starts_with($path, 'embed/') => substr($path, strlen('embed/')),
                str_starts_with($path, 'live/') => substr($path, strlen('live/')),
                default => null,
            };

            return self::youtube($id);
        }

        if ($host === 'youtu.be') {
            return self::youtube($path);
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            // vimeo.com/123456789 of player.vimeo.com/video/123456789;
            // een eventueel privacy-hash-segment erna laten we vallen.
            if (preg_match('#^(?:video/)?(\d+)#', $path, $matches)) {
                return "https://player.vimeo.com/video/{$matches[1]}";
            }
        }

        return null;
    }

    private static function youtube(?string $id): ?string
    {
        if (blank($id) || ! preg_match('/^[\w-]{5,20}$/', $id)) {
            return null;
        }

        return "https://www.youtube-nocookie.com/embed/{$id}";
    }
}
