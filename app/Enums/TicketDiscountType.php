<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketDiscountType: string implements HasColor, HasLabel
{
    case FixedPrice = 'fixed_price';
    case BuyXGetY = 'buy_x_get_y';

    public function getLabel(): string
    {
        return match ($this) {
            self::FixedPrice => 'Vaste promoprijs',
            self::BuyXGetY => 'Koop X + Y gratis',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::FixedPrice => 'success',
            self::BuyXGetY => 'info',
        };
    }
}
