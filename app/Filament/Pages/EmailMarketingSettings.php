<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Kit-koppeling (e-maillijst): elke betaalde ticketbestelling zet de koper op
 * de lijst via SubscribeTicketBuyerToKitJob. Zonder API-key staat de feature
 * gewoon uit.
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
                            ->helperText('Kit → Settings → Developer → API Keys. Leeg = de KIT_API_KEY uit .env (indien gezet).'),
                        TextInput::make('kit_form_id')
                            ->label('Formulier-ID (optioneel)')
                            ->numeric()
                            ->helperText('Koppelt de koper aan dit Kit-formulier. Leeg = alleen als subscriber toevoegen.'),
                        TextInput::make('kit_tag_id')
                            ->label('Tag-ID (optioneel)')
                            ->numeric()
                            ->helperText('Geeft de koper deze tag, bv. "ticketkopers". Handig om te segmenteren.'),
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
}
