<?php

namespace App\Jobs;

use App\Services\Translation\TranslateRecord;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Vertaalt één record op de achtergrond. De bulk-actie zet per geselecteerd
 * item zo'n job in een batch; zo blijft geen enkele HTTP-request minutenlang
 * hangen op een rij API-calls (de oorzaak van de afgebroken bulk-vertaling
 * op Combell).
 *
 * Mislukt de job definitief, dan bewaart failed() de weergavenaam onder de
 * batch-id zodat de klaar-melding kan zeggen wélk item de klant moet
 * herproberen.
 */
class TranslateRecordJob implements ShouldQueue
{
    use Batchable, Queueable;

    /** Eén automatische retry vangt eenmalige API-haperingen op. */
    public int $tries = 2;

    public int $backoff = 10;

    /**
     * Een grote pagina gaat in meerdere API-calls en mag dus lang duren.
     * Let op: hoort onder queue.connections.database.retry_after te blijven,
     * anders geeft de wachtrij de job een tweede keer uit terwijl hij nog loopt.
     */
    public int $timeout = 600;

    public function __construct(
        public Model $record,
        public string $locale,
        public string $label,
    ) {}

    public static function failedLabelsCacheKey(string $batchId): string
    {
        return "bulk-translate:{$batchId}:failed";
    }

    public function handle(TranslateRecord $translator): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $translator->handle($this->record, $this->locale);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Vertaling mislukt', ['record' => $this->record->getKey(), 'exception' => $exception]);

        if ($this->batchId === null) {
            return;
        }

        $key = self::failedLabelsCacheKey($this->batchId);
        $labels = Cache::get($key, []);
        $labels[] = $this->label;
        Cache::put($key, $labels, now()->addDay());
    }
}
