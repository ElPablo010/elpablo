<?php

namespace App\Filament\Resources\TicketTypes;

use App\Filament\Resources\TicketTypes\Pages\ManageTicketTypes;
use App\Models\TicketType;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Globale tickettype-catalogus ("Standaard", "VIP", …). Bewust compact — een
 * simpele lijst met modals volstaat; de echte verkoopgegevens (prijs per
 * event, capaciteit, verkoopvenster) leven op de Tickets-tab van het event.
 */
class TicketTypeResource extends Resource
{
    protected static ?string $model = TicketType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'Events';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return 'tickettype';
    }

    public static function getPluralModelLabel(): string
    {
        return 'tickettypes';
    }

    public static function getNavigationLabel(): string
    {
        return 'Tickettypes';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    TextInput::make('name')
                        ->label('Naam (NL)')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('name_en')
                        ->label('Naam (EN)')
                        ->maxLength(255)
                        ->helperText('Leeg = de NL-naam.'),
                    TextInput::make('name_es')
                        ->label('Naam (ES)')
                        ->maxLength(255)
                        ->helperText('Leeg = de NL-naam.'),
                ]),
            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('default_price')
                        ->label('Standaardprijs (incl. btw)')
                        ->numeric()
                        ->prefix('€')
                        ->helperText('Vooringevuld bij het koppelen aan een event.'),
                    TextInput::make('default_vat_rate')
                        ->label('Standaard btw-tarief (%)')
                        ->numeric()
                        ->default(21)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->label('Naam')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('default_price')
                    ->label('Standaardprijs')
                    ->money('EUR')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('default_vat_rate')
                    ->label('Btw')
                    ->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 2, ',', '.'), '0'), ',').'%'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bewerken'),
                \Filament\Actions\DeleteAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->tooltip('Verwijderen'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTicketTypes::route('/'),
        ];
    }
}
