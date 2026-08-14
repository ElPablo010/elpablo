<?php

namespace App\Filament\Resources\TicketOrders;

use App\Enums\OrderStatus;
use App\Filament\Resources\TicketOrders\Pages\ListTicketOrders;
use App\Filament\Resources\TicketOrders\Pages\ViewTicketOrder;
use App\Filament\Resources\TicketOrders\RelationManagers\TicketsRelationManager;
use App\Models\TicketOrder;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Bestellingen zijn read-mostly: ze ontstaan uitsluitend via de publieke
 * checkout. De admin kan bekijken, tickets opnieuw verzenden, handmatig
 * in-/uitchecken (via de tickets-relatie) en terugbetalen.
 */
class TicketOrderResource extends Resource
{
    protected static ?string $model = TicketOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'Events';

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return 'bestelling';
    }

    public static function getPluralModelLabel(): string
    {
        return 'bestellingen';
    }

    public static function getNavigationLabel(): string
    {
        return 'Bestellingen';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** Badge: betaalde bestellingen van de voorbije 7 dagen. */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()
            ->where('status', OrderStatus::Paid)
            ->where('paid_at', '>=', now()->subDays(7))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bestelling')
                ->columns(3)
                ->schema([
                    TextEntry::make('event.name')->label('Event'),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('created_at')->label('Besteld op')->dateTime('d/m/Y H:i'),
                    TextEntry::make('buyer_name')->label('Naam'),
                    TextEntry::make('buyer_email')->label('E-mail')->copyable(),
                    TextEntry::make('locale')->label('Taal')->formatStateUsing(fn (string $state): string => strtoupper($state)),
                ]),

            Section::make('Bedragen')
                ->columns(3)
                ->schema([
                    TextEntry::make('subtotal_inc_vat')->label('Subtotaal')->money('EUR'),
                    TextEntry::make('discount_amount')
                        ->label('Korting')
                        ->money('EUR')
                        ->placeholder('—')
                        ->helperText(fn (TicketOrder $record): ?string => $record->discountCode?->code),
                    TextEntry::make('total_inc_vat')->label('Totaal')->money('EUR'),
                    TextEntry::make('items')
                        ->label('Regels')
                        ->columnSpanFull()
                        ->state(fn (TicketOrder $record): string => $record->items
                            ->map(fn ($item) => "{$item->quantity} × {$item->description}".
                                ($item->free_quantity > 0 ? " ({$item->free_quantity} gratis)" : '').
                                ' — € '.number_format((float) $item->line_total_inc_vat, 2, ',', '.').
                                ' ('.rtrim(rtrim(number_format((float) $item->vat_rate, 2, ',', '.'), '0'), ',').'% btw)')
                            ->join("\n")),
                ]),

            Section::make('Betaling')
                ->columns(3)
                ->schema([
                    TextEntry::make('paid_at')->label('Betaald op')->dateTime('d/m/Y H:i')->placeholder('—'),
                    TextEntry::make('stripe_session_id')->label('Stripe-sessie')->placeholder('—')->copyable(),
                    TextEntry::make('refunded_at')->label('Terugbetaald op')->dateTime('d/m/Y H:i')->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['event', 'discountCode'])->withCount('tickets'))
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('buyer_name')
                    ->label('Koper')
                    ->searchable(['buyer_name', 'buyer_email'])
                    ->description(fn (TicketOrder $record): string => $record->buyer_email),
                TextColumn::make('event.name')
                    ->label('Event')
                    ->sortable(),
                TextColumn::make('tickets_count')
                    ->label('Tickets')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('total_inc_vat')
                    ->label('Totaal')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('paid_at')
                    ->label('Betaald op')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('event_id')
                    ->label('Event')
                    ->relationship('event', 'name', fn (Builder $query) => $query->orderBy('name')),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(OrderStatus::class),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bekijken'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTicketOrders::route('/'),
            'view' => ViewTicketOrder::route('/{record}'),
        ];
    }
}
