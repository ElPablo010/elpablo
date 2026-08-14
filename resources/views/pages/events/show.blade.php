<x-layouts.site
    :title="$seo['title']"
    :description="$seo['description']"
    :canonical="$seo['canonical']"
    :robots="$seo['robots']"
    :image="$seo['image']"
    :image-alt="$seo['imageAlt']"
    :image-width="$seo['imageWidth']"
    :image-height="$seo['imageHeight']"
    :type="$seo['type']"
    :schema="$seo['schema']"
    :locale="$seo['locale']"
    :alternates="$seo['alternates']"
>
    <section class="relative overflow-hidden bg-ink-950">
        <div class="pointer-events-none absolute -top-32 -left-24 h-96 w-96 rounded-full bg-primary-600/20 blur-3xl"></div>

        <div class="relative mx-auto w-full max-w-7xl px-4 pb-24 pt-36 sm:pt-44 lg:px-6">
            <a href="{{ \App\Support\Locale::href('/events') }}"
               class="inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-400 transition-colors hover:text-primary-500">
                <x-lucide-arrow-left class="h-4 w-4" />
                {{ __('Alle events') }}
            </a>

            @if ($event->isCancelled())
                <div class="mt-8 flex items-start gap-3 rounded-xl border border-red-500/30 bg-red-500/10 p-5 text-red-300" role="alert">
                    <x-lucide-alert-triangle class="mt-0.5 h-5 w-5 shrink-0" />
                    <div>
                        <p class="font-semibold text-red-200">{{ __('Dit event is afgelast.') }}</p>
                        @if ($event->cancellation_message)
                            <p class="mt-1 text-sm">{{ $event->cancellation_message }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="mt-8 grid gap-12 lg:grid-cols-[1fr_28rem]">
                <div class="min-w-0">
                    <p class="eyebrow mb-5">{{ $event->dateLabel() }}</p>
                    <h1 class="font-display text-[2.4rem] leading-[0.95] text-white sm:text-5xl lg:text-6xl">
                        {{ $event->translated('name') }}
                    </h1>

                    <dl class="mt-8 space-y-3 text-gray-300">
                        <div class="flex items-center gap-3">
                            <x-lucide-calendar class="h-5 w-5 shrink-0 text-primary-500" />
                            <span>
                                {{ $event->dateLabel() }}
                                @if ($event->startTimeFormatted())
                                    · {{ $event->startTimeFormatted() }}@if ($event->endTimeFormatted()) – {{ $event->endTimeFormatted() }}@endif
                                @endif
                            </span>
                        </div>
                        @if ($event->venue_name || $event->venue_city)
                            <div class="flex items-center gap-3">
                                <x-lucide-map-pin class="h-5 w-5 shrink-0 text-primary-500" />
                                <span>{{ collect([$event->venue_name, $event->venue_address, $event->venue_city])->filter()->join(', ') }}</span>
                            </div>
                        @endif
                    </dl>

                    @if ($event->image_url)
                        <div class="mt-10 overflow-hidden rounded-2xl border border-white/10">
                            <picture>
                                <source srcset="{{ $event->image_url }}" type="image/webp">
                                <img src="{{ $event->image_url }}" alt="{{ $event->image_alt ?? $event->translated('name') }}"
                                     class="w-full object-cover">
                            </picture>
                        </div>
                    @endif

                    @if ($event->translated('description'))
                        <div class="prose-invert-brand mt-10 max-w-none text-gray-300">
                            {!! $event->translated('description') !!}
                        </div>
                    @elseif ($event->translated('short_description'))
                        <p class="mt-10 text-lg leading-relaxed text-gray-300">{{ $event->translated('short_description') }}</p>
                    @endif
                </div>

                <div>
                    @livewire('events.ticket-checkout', ['event' => $event], key('checkout-'.$event->id))
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
