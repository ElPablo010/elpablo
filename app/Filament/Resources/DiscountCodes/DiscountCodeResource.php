<?php

namespace App\Filament\Resources\DiscountCodes;

use App\Enums\DiscountCodeType;
use App\Filament\Resources\DiscountCodes\Pages\ManageDiscountCodes;
use App\Models\DiscountCode;
use App\Models\Event;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DiscountCodeResource extends Resource
{
    protected static ?string $model = DiscountCode::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|\UnitEnum|null $navigationGroup = 'Events';

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getModelLabel(): string
    {
        return 'kortingscode';
    }

    public static function getPluralModelLabel(): string
    {
        return 'kortingscodes';
    }

    public static function getNavigationLabel(): string
    {
        return 'Kortingscodes';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->helperText('Wordt automatisch in hoofdletters bewaard.'),
                    TextInput::make('description')
                        ->label('Omschrijving')
                        ->maxLength(255)
                        ->helperText('Alleen intern zichtbaar.'),
                ]),
            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    Select::make('type')
                        ->label('Soort')
                        ->options(DiscountCodeType::class)
                        ->default(DiscountCodeType::Percentage)
                        ->required()
                        ->live(),
                    TextInput::make('value')
                        ->label('Waarde')
                        ->numeric()
                        ->required()
                        ->prefix(fn (callable $get): ?string => ($get('type') === DiscountCodeType::Fixed || $get('type') === DiscountCodeType::Fixed->value) ? '€' : null)
                        ->suffix(fn (callable $get): ?string => ($get('type') === DiscountCodeType::Percentage || $get('type') === DiscountCodeType::Percentage->value) ? '%' : null),
                    Toggle::make('per_ticket')
                        ->label('Per ticket')
                        ->inline(false)
                        ->visible(fn (callable $get): bool => $get('type') === DiscountCodeType::Fixed || $get('type') === DiscountCodeType::Fixed->value)
                        ->helperText('Het vaste bedrag wordt vermenigvuldigd met het aantal tickets.'),
                ]),
            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    DatePicker::make('valid_from')
                        ->label('Geldig van')
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    DatePicker::make('valid_until')
                        ->label('Geldig t/m')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('valid_from'),
                    TextInput::make('min_order_amount')
                        ->label('Minimaal bestelbedrag')
                        ->numeric()
                        ->prefix('€')
                        ->helperText('Leeg = geen minimum.'),
                ]),
            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    TextInput::make('max_uses')
                        ->label('Max. gebruik (totaal)')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Leeg = onbeperkt. Enkel betaalde bestellingen tellen.'),
                    TextInput::make('max_uses_per_email')
                        ->label('Max. gebruik per e-mailadres')
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Leeg = onbeperkt.'),
                    Toggle::make('is_active')
                        ->label('Actief')
                        ->default(true)
                        ->inline(false),
                ]),
            Select::make('events')
                ->label('Beperkt tot events')
                ->relationship('events', 'name', fn ($query) => $query->orderBy('name'))
                ->multiple()
                ->preload()
                ->helperText('Leeg = geldig voor elk event.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->description(fn (DiscountCode $record): ?string => $record->description),
                TextColumn::make('type')
                    ->label('Soort')
                    ->badge(),
                TextColumn::make('value')
                    ->label('Waarde')
                    ->formatStateUsing(fn (DiscountCode $record): string => $record->type === DiscountCodeType::Percentage
                        ? rtrim(rtrim(number_format((float) $record->value, 2, ',', '.'), '0'), ',').'%'
                        : '€ '.number_format((float) $record->value, 2, ',', '.').($record->per_ticket ? ' / ticket' : '')),
                TextColumn::make('uses')
                    ->label('Gebruikt')
                    ->state(fn (DiscountCode $record): string => $record->usageCount().($record->max_uses !== null ? ' / '.$record->max_uses : '')),
                TextColumn::make('valid_until')
                    ->label('Geldig t/m')
                    ->date('d/m/Y')
                    ->placeholder('Onbeperkt')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Actief')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->color('primary')
                    ->tooltip('Bewerken'),
                DeleteAction::make()
                    ->button()
                    ->hiddenLabel()
                    ->tooltip('Verwijderen'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageDiscountCodes::route('/'),
        ];
    }
}
