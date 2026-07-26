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
