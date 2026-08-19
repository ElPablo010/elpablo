<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Enums\TicketDiscountType;
use App\Filament\Schemas\Components\MediaPickerField;
use App\Models\EventTicketType;
use App\Models\TicketType;
use App\Support\Locale;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Event')
                    ->persistTabInQueryString('tab')
                    ->tabs([
                        Tab::make('Basis')
                            ->id('basis')
                            ->schema(self::basisTab()),
                        Tab::make('Tickets')
                            ->id('tickets')
                            ->schema(self::ticketsTab()),
                        Tab::make('Promo\'s')
                            ->id('promos')
                            ->schema(self::promosTab()),
                        Tab::make('Vertalingen')
                            ->id('translations')
                            ->schema(self::translationsTab()),
                        Tab::make('SEO')
                            ->id('seo')
                            ->schema(self::seoTab()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function basisTab(): array
    {
        return [
            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('name')
                        ->label('Naam')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, callable $get, callable $set): void {
                            if (filled($state) && blank($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Wordt automatisch afgeleid van de naam. Gedeeld over alle talen.'),
                ]),
            Textarea::make('short_description')
                ->label('Korte omschrijving')
                ->rows(2)
                ->maxLength(500)
                ->helperText('Voor de eventkaarten in het overzicht.'),
            RichEditor::make('description')
                ->label('Omschrijving')
                ->extraInputAttributes(['style' => 'min-height: 28rem;']),
            Grid::make(['default' => 1, 'md' => 4])
                ->schema([
                    DatePicker::make('start_date')
                        ->label('Startdatum')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->required(),
                    DatePicker::make('end_date')
                        ->label('Einddatum')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->helperText('Enkel voor meerdaagse events.'),
                    TimePicker::make('start_time')
                        ->label('Startuur')
                        ->seconds(false),
                    TimePicker::make('end_time')
                        ->label('Einduur')
                        ->seconds(false),
                ]),
            Grid::make(['default' => 1, 'md' => 4])
                ->schema([
                    TextInput::make('venue_name')
                        ->label('Locatie')
                        ->maxLength(255),
                    TextInput::make('venue_address')
                        ->label('Adres')
                        ->maxLength(255),
                    TextInput::make('venue_postal_code')
                        ->label('Postcode')
                        ->maxLength(20),
                    TextInput::make('venue_city')
                        ->label('Stad')
                        ->maxLength(255),
                ]),
            MediaPickerField::make('image_url', 'Afbeelding', required: false, helperText: 'Wordt getoond op de eventpagina en in het overzicht.'),
            TextInput::make('image_alt')
                ->label('Afbeelding — alt-tekst')
                ->maxLength(255),
            Grid::make(['default' => 1, 'md' => 2])
                ->schema([
                    Toggle::make('published')
                        ->label('Gepubliceerd'),
                    Toggle::make('is_cancelled')
                        ->label('Afgelast')
                        ->live()
                        ->helperText('Het event blijft zichtbaar met een duidelijke melding; de ticketverkoop stopt.'),
                ]),
            Textarea::make('cancellation_message')
                ->label('Annuleringsboodschap')
                ->rows(2)
                ->visible(fn (callable $get): bool => (bool) $get('is_cancelled')),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function ticketsTab(): array
    {
        return [
            Repeater::make('eventTicketTypes')
                ->relationship()
                ->label('Tickettypes')
                ->addActionLabel('Tickettype toevoegen')
                ->orderColumn('position')
                ->reorderable()
                ->defaultItems(0)
                ->itemLabel(fn (array $state): ?string => TicketType::find($state['ticket_type_id'] ?? null)?->name)
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])
                        ->schema([
                            Select::make('ticket_type_id')
                                ->label('Tickettype')
                                ->options(fn (): array => TicketType::orderBy('name')->pluck('name', 'id')->all())
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (?int $state, callable $get, callable $set): void {
                                    $type = $state ? TicketType::find($state) : null;
                                    if ($type && blank($get('price'))) {
                                        $set('price', $type->default_price);
                                        $set('vat_rate', $type->default_vat_rate);
                                    }
                                })
                                ->helperText('Types beheer je onder Events → Tickettypes.'),
                            TextInput::make('price')
                                ->label('Prijs (incl. btw)')
                                ->numeric()
                                ->prefix('€')
                                ->required(),
                            TextInput::make('vat_rate')
                                ->label('Btw-tarief (%)')
                                ->numeric()
                                ->default(21)
                                ->required(),
                        ]),
                    Grid::make(['default' => 1, 'md' => 3])
                        ->schema([
                            DatePicker::make('sales_end_date')
                                ->label('Verkoop t/m')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->helperText('Leeg = geen deadline.'),
                            TextInput::make('capacity')
                                ->label('Capaciteit')
                                ->numeric()
                                ->minValue(1)
                                ->helperText('Leeg = onbeperkt.'),
                            Toggle::make('sold_out')
                                ->label('Handmatig uitverkocht')
                                ->inline(false),
                        ]),
                    Placeholder::make('sold_count')
                        ->label('Verkocht')
                        ->content(fn (?EventTicketType $record): string => $record
                            ? $record->soldCount().($record->capacity !== null ? ' / '.$record->capacity : '')
                            : '—'),
                ]),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function promosTab(): array
    {
        return [
            Repeater::make('ticketDiscounts')
                ->relationship()
                ->label('Automatische promo\'s')
                ->addActionLabel('Promo toevoegen')
                ->defaultItems(0)
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->schema([
                    Grid::make(['default' => 1, 'md' => 3])
                        ->schema([
                            TextInput::make('name')
                                ->label('Naam')
                                ->required()
                                ->maxLength(255)
                                ->helperText('bv. "Early bird".'),
                            Select::make('ticket_type_id')
                                ->label('Tickettype')
                                ->options(fn (): array => TicketType::orderBy('name')->pluck('name', 'id')->all())
                                ->required(),
                            Select::make('type')
                                ->label('Soort')
                                ->options(TicketDiscountType::class)
                                ->default(TicketDiscountType::FixedPrice)
                                ->required()
                                ->live(),
                        ]),
                    Grid::make(['default' => 1, 'md' => 3])
                        ->schema([
                            TextInput::make('price')
                                ->label('Promoprijs (incl. btw)')
                                ->numeric()
                                ->prefix('€')
                                ->visible(fn (callable $get): bool => $get('type') === TicketDiscountType::FixedPrice || $get('type') === TicketDiscountType::FixedPrice->value)
                                ->required(fn (callable $get): bool => $get('type') === TicketDiscountType::FixedPrice || $get('type') === TicketDiscountType::FixedPrice->value),
                            TextInput::make('buy_quantity')
                                ->label('Koop-aantal')
                                ->numeric()
                                ->minValue(1)
                                ->visible(fn (callable $get): bool => $get('type') === TicketDiscountType::BuyXGetY || $get('type') === TicketDiscountType::BuyXGetY->value)
                                ->required(fn (callable $get): bool => $get('type') === TicketDiscountType::BuyXGetY || $get('type') === TicketDiscountType::BuyXGetY->value),
                            TextInput::make('free_quantity')
                                ->label('Gratis-aantal')
                                ->numeric()
                                ->minValue(1)
                                ->visible(fn (callable $get): bool => $get('type') === TicketDiscountType::BuyXGetY || $get('type') === TicketDiscountType::BuyXGetY->value)
                                ->required(fn (callable $get): bool => $get('type') === TicketDiscountType::BuyXGetY || $get('type') === TicketDiscountType::BuyXGetY->value),
                        ]),
                    Grid::make(['default' => 1, 'md' => 2])
                        ->schema([
                            DatePicker::make('valid_from')
                                ->label('Geldig van')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required(),
                            DatePicker::make('valid_until')
                                ->label('Geldig t/m')
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->required()
                                ->afterOrEqual('valid_from'),
                        ]),
                ]),
        ];
    }

    /**
     * De vertaaltabs schrijven naar statePath translations.{locale}; het laden
     * en bewaren gebeurt in de trait ManagesEventFormData op de pagina's.
     *
     * @return array<int, mixed>
     */
    private static function translationsTab(): array
    {
        $fieldsets = [];

        foreach (Locale::supported() as $locale) {
            if ($locale === Locale::DEFAULT) {
                continue;
            }

            $fieldsets[] = Fieldset::make(Locale::LABELS[$locale] ?? strtoupper($locale))
                ->statePath("translations.{$locale}")
                ->columns(1)
                ->schema([
                    TextInput::make('name')
                        ->label('Naam')
                        ->maxLength(255),
                    Textarea::make('short_description')
                        ->label('Korte omschrijving')
                        ->rows(2)
                        ->maxLength(500),
                    RichEditor::make('description')
                        ->label('Omschrijving')
                        ->extraInputAttributes(['style' => 'min-height: 28rem;']),
                ]);
        }

        return [
            Placeholder::make('translations_hint')
                ->hiddenLabel()
                ->content('Lege velden vallen op de publieke site terug op de Nederlandse tekst.'),
            ...$fieldsets,
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function seoTab(): array
    {
        return [
            TextInput::make('meta_title')
                ->label('Meta-titel')
                ->maxLength(60)
                ->helperText('Ideaal ~60 tekens. Leeg = de eventnaam.'),
            Textarea::make('meta_description')
                ->label('Meta-omschrijving')
                ->rows(3)
                ->maxLength(160)
                ->helperText('Ideaal ~160 tekens. Leeg = de korte omschrijving.'),
        ];
    }
}
