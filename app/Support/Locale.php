<?php

namespace App\Support;

/**
 * Meertaligheid — één bron voor de ondersteunde locales, de labels en het
 * opbouwen van locale-bewuste URL's.
 *
 * NL is de hoofdtaal en draait op de root (geen prefix); EN en ES draaien onder
 * /en en /es. Pagina's delen dezelfde slug per taal (unique op [locale, slug]),
 * dus een interne link wordt gelokaliseerd door simpelweg de locale-prefix voor
 * het pad te zetten.
 */
class Locale
{
    public const DEFAULT = 'nl';

    /** @var array<string, string> code => label */
    public const LABELS = [
        'nl' => 'NL',
        'en' => 'EN',
        'es' => 'ES',
    ];

    /** @return array<int, string> */
    public static function supported(): array
    {
        return array_keys(self::LABELS);
    }

    public static function current(): string
    {
        return app()->getLocale();
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::supported(), true);
    }

    /**
     * Prefix een intern, root-relatief pad met de (actieve) locale. NL krijgt geen
     * prefix. Externe links, ankers (#...), mailto: en tel: blijven ongemoeid.
     */
    public static function href(string $href, ?string $locale = null): string
    {
        $locale ??= self::current();

        // Alleen interne, root-relatieve paden lokaliseren.
        if (! str_starts_with($href, '/')) {
            return $href;
        }

        if ($locale === self::DEFAULT) {
            return $href;
        }

        return $href === '/' ? '/'.$locale : '/'.$locale.$href;
    }

    /**
     * Basis-pad voor de taalschakelaar: het huidige request-pad zonder
     * locale-prefix. Omdat slugs per taal gedeeld worden (pagina's én events)
     * volstaat het om dit pad opnieuw te prefixen met de doeltaal. Paden
     * zonder taalvariant (ticketstatus, design-previews) wisselen naar home.
     */
    public static function switchBase(): string
    {
        $path = '/'.ltrim(request()->path(), '/');

        foreach (self::supported() as $locale) {
            if ($locale === self::DEFAULT) {
                continue;
            }

            if ($path === '/'.$locale) {
                $path = '/';
                break;
            }

            if (str_starts_with($path, '/'.$locale.'/')) {
                $path = substr($path, strlen('/'.$locale));
                break;
            }
        }

        foreach (['/t/', '/design/'] as $unlocalized) {
            if (str_starts_with($path, $unlocalized)) {
                return '/';
            }
        }

        return $path;
    }

    /**
     * Alle ondersteunde talen behalve de gegeven — de doeltalen voor een
     * vertaling vanuit die taal.
     *
     * @return array<int, string>
     */
    public static function others(string $except): array
    {
        return array_values(array_diff(self::supported(), [$except]));
    }

    /**
     * Voluit geschreven taalnaam voor meldingen en keuzelijsten in de admin
     * ("vertaald naar het Spaans"); LABELS blijft de korte badge-variant.
     */
    public static function name(string $locale): string
    {
        return match ($locale) {
            'nl' => 'Nederlands',
            'en' => 'Engels',
            'es' => 'Spaans',
            default => strtoupper($locale),
        };
    }
}
