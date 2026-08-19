<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\KitApi;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Kit-koppeling (e-maillijst): elke betaalde ticketbestelling zet de koper op
 * de lijst via SubscribeTicketBuyerToKitJob. Zonder API-key staat de feature
 * gewoon uit. Formulier en tag kies je uit dropdowns die live uit het
 * Kit-account geladen worden — niemand hoeft een numeriek ID op te zoeken.
 */
class EmailMarketingSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Instellingen';

    protected static ?string $navigationLabel = 'E-mailmarketing';

    protected static ?string $title = 'E-mailmarketing';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.email-marketing-settings';

    /** @var array<string,mixed> */
    public array $data = [];

    /** Sleutels die 1-op-1 naar de Setting-tabel gaan. */
    protected array $keys = [
        'kit_api_key',
        'kit_form_id',
        'kit_tag_id',
    ];

    public function mount(): void
    {
        $data = [];
        foreach ($this->keys as $key) {
            $data[$key] = Setting::get($key);
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Kit')
                    ->description('Elke betaalde ticketbestelling zet de koper automatisch op je Kit-lijst. Laat de API-key leeg om dit uit te schakelen.')
                    ->schema([
                        TextInput::make('kit_api_key')
                            ->label('Kit API-key (v4)')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            // Na het invullen herladen de dropdowns hieronder
                            // meteen met de formulieren en tags van dit account.
                            ->live(onBlur: true)
                            ->helperText('Kit → Settings → Developer → API Keys. Leeg = de KIT_API_KEY uit .env (indien gezet).'),
                        Select::make('kit_form_id')
                            ->label('Formulier (optioneel)')
                            ->options(fn (Get $get) => $this->withCurrent(
                                KitApi::formOptions($get('kit_api_key')),
                                $get('kit_form_id'),
                                'Formulier',
                            ))
                            ->searchable()
                            ->nullable()
                            ->placeholder(fn (Get $get) => KitApi::apiKey($get('kit_api_key'))
                                ? 'Kies een formulier'
                                : 'Vul eerst de API-key in')
                            ->helperText('Koppelt de koper aan dit Kit-formulier. Leeg = alleen als subscriber toevoegen.'),
                        Select::make('kit_tag_id')
                            ->label('Tag (optioneel)')
                            ->options(fn (Get $get) => $this->withCurrent(
                                KitApi::tagOptions($get('kit_api_key')),
                                $get('kit_tag_id'),
                                'Tag',
                            ))
                            ->searchable()
                            ->nullable()
                            ->placeholder(fn (Get $get) => KitApi::apiKey($get('kit_api_key'))
                                ? 'Kies een tag'
                                : 'Vul eerst de API-key in')
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nieuwe tag')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Wordt meteen aangemaakt in je Kit-account, bv. "ticketkopers".'),
                            ])
                            ->createOptionUsing(function (array $data, Get $get): ?int {
                                $tagId = KitApi::createTag($data['name'], $get('kit_api_key'));

                                if ($tagId === null) {
                                    Notification::make()
                                        ->title('Tag aanmaken mislukt')
                                        ->body('Kit was niet bereikbaar of de API-key klopt niet.')
                                        ->danger()
                                        ->send();
                                }

                                return $tagId;
                            })
                            ->helperText('Geeft de koper deze tag — handig om te segmenteren. Staat hij er nog niet, maak hem dan hier meteen aan.'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($this->keys as $key) {
            Setting::set($key, $state[$key] ?? null);
        }

        Notification::make()->title('E-mailmarketing-instellingen opgeslagen')->success()->send();
    }

    /**
     * Zorgt dat een eerder bewaarde keuze zichtbaar blijft, ook als Kit
     * onbereikbaar is of het id intussen niet meer bestaat — anders lijkt het
     * veld leeg terwijl er wél iets is ingesteld.
     *
     * @param  array<int|string, string>  $options
     * @return array<int|string, string>
     */
    protected function withCurrent(array $options, int|string|null $current, string $label): array
    {
        if (filled($current) && ! array_key_exists($current, $options)) {
            $options[$current] = "{$label} #{$current} (niet gevonden in Kit)";
        }

        return $options;
    }
}
