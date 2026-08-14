<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Wekelijkse SEO-briefing: verse posities ophalen, AI-advies + verbeteracties
// genereren en de stand van zaken mailen. Maandagochtend, zodat het rapport er
// staat bij de start van de week.
Schedule::command('seo:weekly-report')->weeklyOn(1, '7:00');

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
