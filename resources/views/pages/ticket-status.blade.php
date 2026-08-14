<x-layouts.site
    :title="__('Ticketstatus')"
    :description="''"
    robots="noindex, nofollow"
>
    <section class="relative overflow-hidden bg-ink-950">
        <div class="pointer-events-none absolute -top-32 -left-24 h-96 w-96 rounded-full bg-primary-600/20 blur-3xl"></div>

        <div class="relative mx-auto w-full max-w-2xl px-4 pb-24 pt-36 sm:pt-44 lg:px-6">
            @if ($ticket === null)
                <p class="eyebrow mb-5">{{ __('Ticketstatus') }}</p>
                <h1 class="font-display text-[2.4rem] leading-[0.95] text-white sm:text-5xl">{{ __('Ticket niet gevonden') }}</h1>
                <p class="mt-6 text-lg leading-relaxed text-gray-400">
                    {{ __('Deze QR-code hoort niet bij een geldig ticket.') }}
                </p>
            @else
                @php
                    $status = $ticket->status;
                    [$panel, $icon, $label] = match ($status) {
                        \App\Enums\TicketStatus::Paid => ['border-green-500/30 bg-green-500/10 text-green-300', 'lucide-badge-check', __('Geldig ticket')],
                        \App\Enums\TicketStatus::CheckedIn => ['border-sky-500/30 bg-sky-500/10 text-sky-300', 'lucide-door-open', __('Al ingecheckt')],
                        \App\Enums\TicketStatus::Refunded => ['border-red-500/30 bg-red-500/10 text-red-300', 'lucide-undo-2', __('Dit ticket werd terugbetaald.')],
                        default => ['border-amber-500/30 bg-amber-500/10 text-amber-300', 'lucide-clock', __('Nog niet betaald')],
                    };
                @endphp

                <p class="eyebrow mb-5">{{ __('Ticketstatus') }}</p>
                <h1 class="font-display text-[2.4rem] leading-[0.95] text-white sm:text-5xl">
                    {{ $ticket->event->translated('name') }}
                </h1>

                <div class="mt-8 flex items-start gap-3 rounded-2xl border p-6 {{ $panel }}" role="status">
                    <x-dynamic-component :component="$icon" class="mt-0.5 h-6 w-6 shrink-0" />
                    <div>
                        <p class="text-lg font-semibold">{{ $label }}</p>
                        @if ($status === \App\Enums\TicketStatus::CheckedIn && $ticket->checkedInAtLocal())
                            <p class="mt-1 text-sm">{{ __('Ingecheckt om :time', ['time' => $ticket->checkedInAtLocal()->format('H:i')]) }}</p>
                        @endif
                    </div>
                </div>

                <dl class="mt-8 space-y-3 text-gray-300">
                    <div class="flex items-center gap-3">
                        <x-lucide-ticket class="h-5 w-5 shrink-0 text-primary-500" />
                        <span>{{ $ticket->ticketType->nameFor() }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-lucide-calendar class="h-5 w-5 shrink-0 text-primary-500" />
                        <span>{{ $ticket->event->dateLabel() }}@if ($ticket->event->startTimeFormatted()) · {{ $ticket->event->startTimeFormatted() }}@endif</span>
                    </div>
                    @if ($ticket->event->venue_name || $ticket->event->venue_city)
                        <div class="flex items-center gap-3">
                            <x-lucide-map-pin class="h-5 w-5 shrink-0 text-primary-500" />
                            <span>{{ collect([$ticket->event->venue_name, $ticket->event->venue_city])->filter()->join(', ') }}</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-3">
                        <x-lucide-user class="h-5 w-5 shrink-0 text-primary-500" />
                        <span>{{ $ticket->order->buyer_name }}</span>
                    </div>
                </dl>
            @endif
        </div>
    </section>
</x-layouts.site>
