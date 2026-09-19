<?php

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * Zet "Events" in het hoofdmenu en de footernavigatie, net vóór Contact.
 * Menu's leven in de database, dus een bestaande site (lokaal én live) krijgt
 * dit niet via de seeder — die zou de hele content overschrijven.
 * Idempotent: bestaat het item al, dan gebeurt er niets.
 */
return new class extends Migration
{
    private const LOCATIONS = ['main', 'footer_1'];

    public function up(): void
    {
        foreach (self::LOCATIONS as $location) {
            $menu = Menu::where('location', $location)->first();

            if ($menu === null || $menu->allItems()->where('url', '/events')->exists()) {
                continue;
            }

            // Vlak vóór Contact; is dat er niet, dan achteraan.
            $contact = $menu->allItems()->whereNull('parent_id')->where('label', 'Contact')->first();
            $position = $contact?->position ?? (((int) $menu->allItems()->max('position')) + 1);

            $menu->allItems()
                ->whereNull('parent_id')
                ->where('position', '>=', $position)
                ->increment('position');

            $menu->allItems()->create([
                'label' => 'Events',
                'url' => '/events',
                'position' => $position,
            ]);
        }
    }

    public function down(): void
    {
        foreach (self::LOCATIONS as $location) {
            $menu = Menu::where('location', $location)->first();

            if ($menu === null) {
                continue;
            }

            MenuItem::where('menu_id', $menu->id)->where('url', '/events')->delete();
        }
    }
};
