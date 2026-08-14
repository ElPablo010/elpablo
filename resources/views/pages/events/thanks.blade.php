<x-layouts.site
    :title="__('Bedankt voor je bestelling!')"
    :description="''"
    robots="noindex, follow"
    :locale="$locale"
>
    <section class="relative overflow-hidden bg-ink-950">
        <div class="pointer-events-none absolute -top-32 -left-24 h-96 w-96 rounded-full bg-primary-600/20 blur-3xl"></div>

        <div class="relative mx-auto w-full max-w-3xl px-4 pb-24 pt-36 sm:pt-44 lg:px-6">
            @if ($order)
                <p class="eyebrow mb-5">{{ $event->translated('name') }}</p>
                <h1 class="font-display text-[2.4rem] leading-[0.95] text-white sm:text-5xl">
                    {{ __('Bedankt voor je bestelling, :name!', ['name' => explode(' ', $order->buyer_name)[0]]) }}
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-gray-300">
                    {{ __('Je betaling is gelukt. Je tickets komen eraan per e-mail (:email), als PDF met QR-code in de bijlage.', ['email' => $order->buyer_email]) }}
                </p>

                <div class="mt-10 rounded-2xl border border-white/10 bg-ink-900 p-6 sm:p-8">
                    <h2 class="font-display text-xl text-white">{{ __('Je bestelling') }}</h2>
                    <div class="mt-4 space-y-1.5 text-sm">
                        @foreach ($order->items as $item)
                            <div class="flex justify-between text-gray-300">
                                <span>
                                    {{ $item->quantity }} × {{ $item->description }}
                                    @if ($item->free_quantity > 0)
                                        <span class="text-primary-500">({{ __(':count gratis', ['count' => $item->free_quantity]) }})</span>
                                    @endif
                                </span>
                                <span>€ {{ number_format((float) $item->line_total_inc_vat, 2, ',', '.') }}</span>
                            </div>
                        @endforeach
                        @if ((float) $order->discount_amount > 0)
                            <div class="flex justify-between text-green-400">
                                <span>{{ __('Korting') }}</span>
                                <span>− € {{ number_format((float) $order->discount_amount, 2, ',', '.') }}</span>
                            </div>
                        @endif
                        <div class="flex justify-between border-t border-white/10 pt-3 text-base font-semibold text-white">
                            <span>{{ __('Totaal') }}</span>
                            <span>€ {{ number_format((float) $order->total_inc_vat, 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <p class="mt-6 text-sm text-gray-500">
                    {{ __('Geen mail ontvangen binnen het kwartier? Kijk even in je spamfolder.') }}
                </p>
            @else
                <h1 class="font-display text-[2.4rem] leading-[0.95] text-white sm:text-5xl">
                    {{ __('Bedankt!') }}
                </h1>
                <p class="mt-6 text-lg leading-relaxed text-gray-300">
                    {{ __('We verwerken je betaling. Zodra ze bevestigd is, ontvang je je tickets per e-mail — meestal binnen enkele minuten.') }}
                </p>
            @endif

            <div class="mt-10">
                <a href="{{ \App\Support\Locale::href('/events') }}" class="btn-secondary">
                    <x-lucide-arrow-left class="h-4 w-4" />
                    {{ __('Alle events') }}
                </a>
            </div>
        </div>
    </section>
</x-layouts.site>
