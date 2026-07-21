<?php

namespace App\Support;

use Illuminate\Support\Str;

class Url
{
    /**
     * Normaliseer een door de klant ingegeven link tot een bruikbare href.
     *
     * Interne paden (`/over-ons`), ankers (`#contact`) en volledige URLs
     * (http/https/mailto/tel) blijven ongemoeid. Een kale domeinnaam zoals
     * `www.bailandolatino.be` wordt een externe https-link — anders ziet de
     * browser die als relatief pad en plakt hij hem achter de huidige URL.
     */
    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['/', '#', 'http://', 'https://', 'mailto:', 'tel:'])) {
            return $value;
        }

        return 'https://'.$value;
    }
}
