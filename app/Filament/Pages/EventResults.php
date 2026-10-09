<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\Event;
use App\Support\EventResult;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Overzicht van het resultaat per event (opbrengsten − kosten, excl. btw),
 * per jaar. De cijfers zelf komen uit EventResult; kosten en andere
 * opbrengsten geef je in op de tab Resultaat van elk event.
 */
class EventResults extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Tickets';

    protected static ?string $navigationLabel = 'Resultaten';

    protected static ?string $title = 'Event-resultaten';

    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.pages.event-results';

    public ?int $year = null;

    public function mount(): void
    {
        $this->year = (int) now()->year;
    }

    /** @return array<int, int> */
    public function getYearOptionsProperty(): array
    {
        return Event::query()
            ->pluck('start_date')
            ->map(fn ($date): int => (int) $date->year)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /** @return Collection<int, array{event: Event, result: EventResult, url: string}> */
    public function getRowsProperty(): Collection
    {
        return Event::query()
            ->whereYear('start_date', $this->year ?? now()->year)
            ->with(['paidOrders.items', 'paidOrders.extras', 'costs', 'revenues'])
            ->withCount(['tickets as visitors_count' => fn ($query) => $query->whereIn('status', EventResult::visitorStatuses())])
            ->orderBy('start_date')
            ->get()
            ->map(fn (Event $event): array => [
                'event' => $event,
                'result' => EventResult::for($event),
                'url' => EventResource::getUrl('edit', ['record' => $event]).'?tab=resultaat::data::tab',
            ]);
    }

    public function getTotalsProperty(): EventResult
    {
        $results = $this->rows->pluck('result');

        return new EventResult(
            ticketRevenue: round((float) $results->sum('ticketRevenue'), 2),
            otherRevenue: round((float) $results->sum('otherRevenue'), 2),
            costs: round((float) $results->sum('costs'), 2),
            visitors: (int) $results->sum('visitors'),
        );
    }
}
