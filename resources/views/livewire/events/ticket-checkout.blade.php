<div>
    @php
        $fmt = fn (float $amount): string => '€ '.number_format($amount, 2, ',', '.');
    @endphp

    <div class="rounded-2xl border border-white/10 bg-ink-900 p-6 sm:p-8">
        <h2 class="font-display text-2xl text-white sm:text-3xl">{{ __('Tickets') }}</h2>

        {{-- Tickettypes met stepper --}}
        <div class="mt-6 divide-y divide-white/10">
            @forelse ($this->lines as $line)
                <div class="flex flex-wrap items-center gap-4 py-4">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-white">{{ $line['name'] }}</p>

                        <p class="mt-0.5 text-sm text-gray-400">
                            @if ($line['price']['discount'])
                                <span class="text-gray-500 line-through">{{ $fmt($line['price']['regular']) }}</span>
                                <span class="ml-1 font-semibold text-primary-500">{{ $fmt($line['price']['current']) }}</span>
                                <span class="ml-1 text-primary-500/80">· {{ $line['price']['discount']['name'] }}</span>
                            @else
                                {{ $fmt($line['price']['current']) }}
                            @endif
                        </p>

                        @if ($line['bogo'])
                            <p class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-primary-600/15 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-primary-500">
                                <x-lucide-gift class="h-3.5 w-3.5" />
                                {{ __('Koop :buy, :free gratis', ['buy' => $line['bogo']->buy_quantity, 'free' => $line['bogo']->free_quantity]) }}
                            </p>
                        @endif

                        @if ($line['buyable'] && $line['remaining'] !== null && $line['remaining'] <= 10)
                            <p class="mt-1 text-xs font-medium text-amber-400">{{ __('Nog :count beschikbaar', ['count' => $line['remaining']]) }}</p>
                        @endif
                    </div>

                    @if (! $line['sales_open'])
                        <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Verkoop afgesloten') }}</span>
                    @elseif ($line['sold_out'])
                        <span class="rounded-full bg-red-500/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-red-400">{{ __('Uitverkocht') }}</span>
                    @else
                        <div class="flex items-center gap-3">
                            <button type="button"
                                    wire:click="decrement({{ $line['pivot']->ticket_type_id }})"
                                    @disabled($line['quantity'] < 1)
                                    class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-white/15 text-white transition-colors hover:border-primary-500 disabled:cursor-not-allowed disabled:opacity-40"
                                    aria-label="{{ __('Minder') }}">
                                <x-lucide-minus class="h-4 w-4" />
                            </button>
                            <span class="w-6 text-center font-semibold text-white">{{ $line['quantity'] }}</span>
                            <button type="button"
                                    wire:click="increment({{ $line['pivot']->ticket_type_id }})"
                                    class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-white/15 text-white transition-colors hover:border-primary-500"
                                    aria-label="{{ __('Meer') }}">
                                <x-lucide-plus class="h-4 w-4" />
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <p class="py-4 text-gray-400">{{ __('Voor dit event zijn er (nog) geen tickets beschikbaar.') }}</p>
            @endforelse
        </div>
        @error('quantities') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror

        @if ($this->ticketCount > 0)
            {{-- Kortingscode --}}
            <div class="mt-6 border-t border-white/10 pt-6">
                @if ($appliedCode !== '')
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-2.5 text-sm text-green-300">
                        <span class="inline-flex items-center gap-2">
                            <x-lucide-badge-check class="h-4 w-4 shrink-0" />
                            {{ __('Kortingscode :code toegepast', ['code' => $appliedCode]) }}
                        </span>
                        <button type="button" wire:click="removeDiscountCode"
                                class="cursor-pointer font-semibold text-green-200 underline-offset-2 hover:underline">
                            {{ __('Verwijderen') }}
                        </button>
                    </div>
                @else
                    {{-- text-sm op het veld: zo blijft de knop (die meestretcht met de
                         rij) exact even hoog als de afrekenknop onderaan. --}}
                    <div class="flex gap-3">
                        <input type="text" wire:model="discountCode" wire:keydown.enter="applyDiscountCode"
                               placeholder="{{ __('Kortingscode') }}"
                               class="w-full rounded-lg border border-white/10 bg-ink-950 px-4 py-2.5 text-sm text-white placeholder-gray-500 transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 sm:max-w-xs">
                        <button type="button" wire:click="applyDiscountCode"
                                class="btn-secondary shrink-0">{{ __('Toepassen') }}</button>
                    </div>
                    @if ($codeError)
                        <p class="mt-1.5 text-sm text-red-400">{{ $codeError }}</p>
                    @endif
                @endif
            </div>

            {{-- Totalen --}}
            <div class="mt-6 space-y-1.5 border-t border-white/10 pt-6 text-sm">
                @foreach ($this->orderLines as $line)
                    <div class="flex justify-between text-gray-300">
                        <span>
                            {{ $line['quantity'] }} × {{ $line['name'] }}
                            @if ($line['free'] > 0)
                                <span class="text-primary-500">({{ __(':count gratis', ['count' => $line['free']]) }})</span>
                            @endif
                        </span>
                        <span>{{ $fmt($line['total_inc_vat']) }}</span>
                    </div>
                @endforeach

                @if ($this->discountAmount > 0)
                    <div class="flex justify-between text-green-400">
                        <span>{{ __('Korting') }} ({{ $appliedCode }})</span>
                        <span>− {{ $fmt($this->discountAmount) }}</span>
                    </div>
                @endif

                <div class="flex justify-between border-t border-white/10 pt-3 text-base font-semibold text-white">
                    <span>{{ __('Totaal') }}</span>
                    <span>{{ $fmt($this->total) }}</span>
                </div>
                <p class="text-xs text-gray-500">{{ __('Inclusief btw') }}</p>
            </div>

            {{-- Koper + afrekenen --}}
            <form wire:submit="checkout" class="mt-6 space-y-5 border-t border-white/10 pt-6">
                {{-- Honeypot — verborgen voor mensen, ingevuld door bots. --}}
                <div class="hidden" aria-hidden="true">
                    <label>Laat dit veld leeg
                        <input type="text" wire:model="website" tabindex="-1" autocomplete="off">
                    </label>
                </div>

                {{-- Naam en e-mail elk op hun eigen regel. --}}
                <div class="grid gap-5">
                    <div>
                        <label for="tc-name" class="mb-1.5 block text-sm font-medium text-gray-200">{{ __('Je naam') }}</label>
                        <input id="tc-name" type="text" wire:model="buyerName" autocomplete="name"
                               class="w-full rounded-lg border border-white/10 bg-ink-950 px-4 py-2.5 text-white placeholder-gray-500 transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        @error('buyerName') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tc-email" class="mb-1.5 block text-sm font-medium text-gray-200">{{ __('Je e-mailadres') }}</label>
                        <input id="tc-email" type="email" wire:model="buyerEmail" autocomplete="email"
                               class="w-full rounded-lg border border-white/10 bg-ink-950 px-4 py-2.5 text-white placeholder-gray-500 transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                        @error('buyerEmail') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                        <p class="mt-1.5 text-xs text-gray-500">{{ __('Je ontvangt je tickets op dit adres.') }}</p>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full justify-center sm:w-auto" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="checkout">{{ __('Afrekenen') }} — {{ $fmt($this->total) }}</span>
                    <span wire:loading wire:target="checkout">{{ __('Even geduld…') }}</span>
                    <x-lucide-arrow-right class="h-4 w-4" wire:loading.remove wire:target="checkout" />
                </button>
                <p class="text-xs text-gray-500">{{ __('Veilig betalen via Stripe (Bancontact, kaart, iDEAL, PayPal).') }}</p>
            </form>
        @endif
    </div>
</div>
