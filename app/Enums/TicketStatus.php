<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketStatus: string implements HasColor, HasLabel
{
    case Reserved = 'reserved';
    case Paid = 'paid';
    case CheckedIn = 'checked_in';
    case Refunded = 'refunded';

    /**
     * Statussen die capaciteit bezetten: een reservering houdt een plek vast tot
     * ze betaald wordt of verloopt; terugbetaalde tickets geven de plek weer vrij.
     *
     * @return array<int, string>
     */
    public static function occupying(): array
    {
        return [self::Reserved->value, self::Paid->value, self::CheckedIn->value];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Reserved => 'Gereserveerd',
            self::Paid => 'Betaald',
            self::CheckedIn => 'Ingecheckt',
            self::Refunded => 'Terugbetaald',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Reserved => 'warning',
            self::Paid => 'success',
            self::CheckedIn => 'info',
            self::Refunded => 'danger',
        };
    }
}
