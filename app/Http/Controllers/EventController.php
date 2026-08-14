<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Event;
use App\Models\TicketOrder;
use App\Support\Locale;
use App\Support\Seo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = $this->setLocale($request);

        $upcoming = Event::query()
            ->published()
            ->upcoming()
            ->with(['eventTicketTypes.ticketType', 'ticketDiscounts', 'translations'])
            ->orderBy('start_date')
            ->get();

        $past = Event::query()
            ->published()
            ->whereDate('start_date', '<', Carbon::today())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '<', Carbon::today()))
            ->with('translations')
            ->orderByDesc('start_date')
            ->limit(6)
            ->get();

        return response()->view('pages.events.index', [
            'upcoming' => $upcoming,
            'past' => $past,
            'seo' => Seo::fromEventIndex($locale),
        ]);
    }

    public function show(Request $request): Response
    {
        $locale = $this->setLocale($request);
        $event = $this->resolveEvent($request);

        return response()->view('pages.events.show', [
            'event' => $event,
            'locale' => $locale,
            'seo' => Seo::fromEvent($event, $locale),
        ]);
    }

    /**
     * Bedankpagina na Stripe Checkout. De webhook is het primaire
     * fulfilment-kanaal; hier proberen we hetzelfde proces synchroon zodat een
     * trage webhook de koper nooit laat wachten op zijn tickets. Fouten worden
     * geslikt — de webhook (met retries) is het vangnet.
     */
    public function thanks(Request $request): Response
    {
        $locale = $this->setLocale($request);
        $event = $this->resolveEvent($request);

        $sessionId = (string) $request->query('session_id');
        $order = null;

        if ($sessionId !== '') {
            rescue(fn () => app(\App\Services\TicketOrderFulfillment::class)->completeFromStripeSessionId($sessionId));

            $order = TicketOrder::query()
                ->where('stripe_session_id', $sessionId)
                ->where('status', OrderStatus::Paid)
                ->with(['items', 'tickets.ticketType'])
                ->first();
        }

        return response()->view('pages.events.thanks', [
            'event' => $event,
            'locale' => $locale,
            'order' => $order,
            'seo' => Seo::fromEvent($event, $locale),
        ]);
    }

    private function setLocale(Request $request): string
    {
        $locale = Locale::isSupported($request->route('locale'))
            ? $request->route('locale')
            : Locale::DEFAULT;
        app()->setLocale($locale);

        return $locale;
    }

    private function resolveEvent(Request $request): Event
    {
        $event = Event::query()
            ->where('slug', $request->route('slug'))
            ->when(! auth()->check(), fn ($query) => $query->where('published', true))
            ->with(['eventTicketTypes.ticketType', 'ticketTypes', 'ticketDiscounts', 'translations'])
            ->first();

        if ($event === null) {
            abort(ResponseAlias::HTTP_NOT_FOUND);
        }

        return $event;
    }
}
