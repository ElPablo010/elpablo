<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Webgoeroe\Core\CorePlugin;
use Webgoeroe\Core\Filament\Pages\ManageMenus;
use Webgoeroe\SeoGrowth\SeoGrowthPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // Meldingen van achtergrondjobs (bv. "vertaling klaar" na een bulk-
            // vertaling, TranslateAction::sendToDatabase) komen in de bel rechtsboven.
            ->databaseNotifications()
            ->colors([
                'primary' => Color::hex('#E01B4B'),
            ])
            ->navigationGroups([
                'Website',
                'Tickets',
                'Groei',
                'Instellingen',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->plugins([
                // Site-basis: pagina's, media, menu's, redirects, header, footer,
                // algemene instellingen, inzendingen en de admin-chrome. De
                // menu's komen uit App\Filament\Pages\ManageMenus (core +
                // "Vertalen met AI").
                CorePlugin::make()->without(ManageMenus::class),
                // Groei-module (Search Console, Analytics, leads, SEO-advies en acties).
                SeoGrowthPlugin::make(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
