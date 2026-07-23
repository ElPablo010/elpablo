<div>
    {{-- Dark-styled contactformulier. De Livewire-logica blijft ongewijzigd. --}}
    @if ($sent)
        <div class="flex items-start gap-3 rounded-xl border border-green-500/30 bg-green-500/10 p-6 text-green-300" role="status">
            <x-lucide-check-circle class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="font-semibold text-green-200">{{ __('Bedankt voor je bericht!') }}</p>
                <p class="mt-1 text-sm">{{ __('We nemen zo snel mogelijk contact met je op.') }}</p>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="space-y-5">
            {{-- Honeypot — verborgen voor mensen, ingevuld door bots. --}}
            <div class="hidden" aria-hidden="true">
                <label>Laat dit veld leeg
                    <input type="text" wire:model="website" tabindex="-1" autocomplete="off">
                </label>
            </div>

            <div>
                <label for="cf-name" class="mb-1.5 block text-sm font-medium text-gray-200">{{ __('Naam') }}</label>
                <input id="cf-name" type="text" wire:model="name"
                       class="w-full rounded-lg border border-white/10 bg-ink-950 px-4 py-2.5 text-white placeholder-gray-500 transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                @error('name') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="cf-email" class="mb-1.5 block text-sm font-medium text-gray-200">{{ __('E-mail') }}</label>
                    <input id="cf-email" type="email" wire:model="email"
                           class="w-full rounded-lg border border-white/10 bg-ink-950 px-4 py-2.5 text-white placeholder-gray-500 transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('email') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cf-phone" class="mb-1.5 block text-sm font-medium text-gray-200">
                        {{ __('Telefoon') }} <span class="text-gray-500">{{ __('(optioneel)') }}</span>
                    </label>
                    <input id="cf-phone" type="tel" wire:model="phone"
                           class="w-full rounded-lg border border-white/10 bg-ink-950 px-4 py-2.5 text-white placeholder-gray-500 transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500">
                    @error('phone') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="cf-message" class="mb-1.5 block text-sm font-medium text-gray-200">{{ __('Bericht') }}</label>
                <textarea id="cf-message" rows="5" wire:model="message"
                          class="w-full rounded-lg border border-white/10 bg-ink-950 px-4 py-2.5 text-white placeholder-gray-500 transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500"></textarea>
                @error('message') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="btn-primary w-full sm:w-auto">
                <span wire:loading.remove wire:target="submit" class="inline-flex items-center gap-2">
                    {{ __('Verstuur bericht') }}
                    <x-lucide-send class="h-4 w-4" />
                </span>
                <span wire:loading wire:target="submit">{{ __('Versturen…') }}</span>
            </button>
        </form>
    @endif
</div>
