<?php

namespace App\Filament\Resources\TicketOrders\Pages;

use App\Filament\Resources\TicketOrders\TicketOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListTicketOrders extends ListRecords
{
    protected static string $resource = TicketOrderResource::class;
}
