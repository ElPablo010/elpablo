<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Groei-module (webgoeroe/seo-growth): de wekelijkse SEO-briefing (maandag
// 7:00) en de syncs van Search Console (6:00) en Analytics (6:15) plant de
// package zelf in. Aan/uit op Groei → SEO-instellingen.

// Queued jobs (o.a. bulk-AI-vertalingen) verwerken zonder permanente daemon:
// elke minuut een worker die stopt zodra de wachtrij leeg is. Vereist op de
// live server enkel de bestaande schedule:run-cron.
Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();

// Verlopen ticketreserveringen (verlaten checkouts) geven hun capaciteit weer
// vrij. De checkout.session.expired-webhook doet dit meestal al; dit is het
// vangnet voor gemiste webhooks.
Schedule::command('events:release-expired-reservations')->everyFiveMinutes();
