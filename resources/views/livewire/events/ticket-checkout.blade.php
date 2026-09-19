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
                            {{-- Invoerveld i.p.v. een teller: bij een groep van
                                 24 tickets wil niemand 24 keer klikken. --}}
                            <input type="number" inputmode="numeric" min="0" max="{{ $line['max'] }}"
                                   wire:model.live.debounce.500ms="quantities.{{ $line['pivot']->ticket_type_id }}"
                                   aria-label="{{ __('Aantal') }}"
                                   class="w-14 rounded-lg border border-white/10 bg-ink-950 px-2 py-1.5 text-center font-semibold text-white transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none">
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

        {{-- Extra's: geen tickets, dus ze tellen niet mee als bezoeker. Een
             extra die de drempel nog niet haalt blijft zichtbaar — dat is net
             de reden om er tickets bij te nemen. --}}
        @if ($this->extras)
            <div class="mt-6 border-t border-white/10 pt-6">
                <h3 class="font-display text-lg text-white">{{ __('Extra\'s') }}</h3>

                <div class="mt-3 space-y-3">
                    @foreach ($this->extras as $extra)
                        <div @class([
                            'flex flex-wrap items-center gap-4 rounded-xl border p-4 transition-colors',
                            'border-white/10 bg-ink-950/60' => $extra['selectable'],
                            'border-white/5 bg-ink-950/30 opacity-60' => ! $extra['selectable'],
                        ])>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-white">
                                    {{ $extra['name'] }}
                                    <span class="ml-1 text-sm font-medium text-primary-500">
                                        {{ $extra['price'] > 0 ? $fmt($extra['price']) : __('gratis') }}
                                    </span>
                                </p>

                                @if ($extra['description'])
                                    <p class="mt-0.5 text-sm text-gray-400">{{ $extra['description'] }}</p>
                                @endif

                                @if ($extra['sold_out'])
                                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-red-400">{{ __('Volzet') }}</p>
                                @elseif (! $extra['unlocked'])
                                    <p class="mt-1 inline-flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-amber-400">
                                        <x-lucide-lock class="h-3.5 w-3.5" />
                                        @if ($extra['ticket_type_name'])
                                            {{ __('Vanaf :count × :type', ['count' => $extra['min_tickets'], 'type' => $extra['ticket_type_name']]) }}
                                        @else
                                            {{ __('Vanaf :count tickets', ['count' => $extra['min_tickets']]) }}
                                        @endif
                                    </p>
                                @elseif ($extra['remaining'] !== null && $extra['remaining'] <= 10)
                                    <p class="mt-1 text-xs font-medium text-amber-400">{{ __('Nog :count beschikbaar', ['count' => $extra['remaining']]) }}</p>
                                @endif
                            </div>

                            @if ($extra['selectable'])
                                @if ($extra['max'] > 1)
                                    <div class="flex items-center gap-3">
                                        <button type="button"
                                                wire:click="decrementExtra({{ $extra['model']->id }})"
                                                @disabled($extra['quantity'] < 1)
                                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-white/15 text-white transition-colors hover:border-primary-500 disabled:cursor-not-allowed disabled:opacity-40"
                                                aria-label="{{ __('Minder') }}">
                                            <x-lucide-minus class="h-4 w-4" />
                                        </button>
                                        <span class="w-6 text-center font-semibold text-white">{{ $extra['quantity'] }}</span>
                                        <button type="button"
                                                wire:click="incrementExtra({{ $extra['model']->id }})"
                                                class="flex h-9 w-9 cursor-pointer items-center justify-center rounded-full border border-white/15 text-white transition-colors hover:border-primary-500"
                                                aria-label="{{ __('Meer') }}">
                                            <x-lucide-plus class="h-4 w-4" />
                                        </button>
                                    </div>
                                @else
                                    <button type="button"
                                            wire:click="toggleExtra({{ $extra['model']->id }})"
                                            @class([
                                                'inline-flex cursor-pointer items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold transition-colors',
                                                'border-primary-500 bg-primary-600/15 text-primary-500' => $extra['quantity'] > 0,
                                                'border-white/15 text-white hover:border-primary-500' => $extra['quantity'] < 1,
                                            ])>
                                        @if ($extra['quantity'] > 0)
                                            <x-lucide-check class="h-4 w-4" />
                                            {{ __('Toegevoegd') }}
                                        @else
                                            <x-lucide-plus class="h-4 w-4" />
                                            {{ __('Toevoegen') }}
                                        @endif
                                    </button>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

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

                @foreach ($this->extraLines as $extraLine)
                    <div class="flex justify-between text-gray-300">
                        <span>{{ $extraLine['quantity'] }} × {{ $extraLine['name'] }}</span>
                        <span>{{ $extraLine['total_inc_vat'] > 0 ? $fmt($extraLine['total_inc_vat']) : __('gratis') }}</span>
                    </div>
                @endforeach

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
