<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Stripe-configuratie voor de ticketverkoop. De secrets leven in de database
 * (Setting) zodat de klant ze zelf kan beheren; de .env-waarden zijn de
 * fallback voor CI/lokaal — zelfde patroon als de Anthropic-key in Algemeen.
 */
class PaymentSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Instellingen';

    protected static ?string $navigationLabel = 'Betalingen';

    protected static ?string $title = 'Betaalinstellingen';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.payment-settings';

    /** @var array<string,mixed> */
    public array $data = [];

    /** Sleutels die 1-op-1 naar de Setting-tabel gaan. */
    protected array $keys = [
        'stripe_secret',
        'stripe_webhook_secret',
        'admin_notification_email',
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
                Section::make('Stripe')
                    ->description('De ticketcheckout rekent af via Stripe Checkout. De webhook-URL voor het Stripe-dashboard is '.url('/stripe/webhook').' (events: checkout.session.completed en checkout.session.expired).')
                    ->schema([
                        TextInput::make('stripe_secret')
                            ->label('Secret key')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('sk_live_… of sk_test_…. Leeg = de STRIPE_SECRET uit .env (indien gezet).'),
                        TextInput::make('stripe_webhook_secret')
                            ->label('Webhook signing secret')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('whsec_…. Leeg = de STRIPE_WEBHOOK_SECRET uit .env (indien gezet).'),
                    ])
                    ->columns(2),

                Section::make('Meldingen')
                    ->schema([
                        TextInput::make('admin_notification_email')
                            ->label('E-mailadres voor foutmeldingen')
                            ->email()
                            ->maxLength(255)
                            ->helperText('Ontvangt een melding wanneer een betaling niet verwerkt raakt. Leeg = het algemene afzenderadres.'),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Opslaan')
                ->icon(Heroicon::OutlinedCheck)
                ->color('primary')
                ->keyBindings(['mod+s'])
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($this->keys as $key) {
            Setting::set($key, $state[$key] ?? null);
        }

        Notification::make()->title('Betaalinstellingen opgeslagen')->success()->send();
    }
}
