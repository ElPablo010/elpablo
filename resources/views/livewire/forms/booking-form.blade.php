<div>
    {{-- Dark-styled boekingsformulier. --}}
    @if ($sent)
        <div class="flex items-start gap-3 rounded-xl border border-green-500/30 bg-green-500/10 p-6 text-green-300" role="status">
            <x-lucide-check-circle class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <p class="font-semibold text-green-200">{{ __('Bedankt voor je aanvraag!') }}</p>
                <p class="mt-1 text-sm">{{ __('Ik bekijk je datum en stuur je snel een voorstel op maat.') }}</p>
            </div>
        </div>
    @else
        @php
            $inputClass = 'w-full rounded-lg border border-white/10 bg-ink-950 px-4 py-2.5 text-white placeholder-gray-500 transition-colors focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500';
        @endphp
        <form wire:submit="submit" class="space-y-5">
            {{-- Honeypot --}}
            <div class="hidden" aria-hidden="true">
                <label>Laat dit veld leeg
                    <input type="text" wire:model="website" tabindex="-1" autocomplete="off">
                </label>
            </div>

            <div>
                <label for="bf-name" class="mb-1.5 block text-sm font-medium text-gray-200">{{ __('Naam') }}</label>
                <input id="bf-name" type="text" wire:model="name" class="{{ $inputClass }}">
                @error('name') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="bf-email" class="mb-1.5 block text-sm font-medium text-gray-200">{{ __('E-mail') }}</label>
                    <input id="bf-email" type="email" wire:model="email" class="{{ $inputClass }}">
                    @error('email') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="bf-phone" class="mb-1.5 block text-sm font-medium text-gray-200">{{ __('Telefoon') }}</label>
                    <input id="bf-phone" type="tel" wire:model="phone" class="{{ $inputClass }}">
                    @error('phone') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="bf-date" class="mb-1.5 block text-sm font-medium text-gray-200">
                        {{ __('Datum feest') }} <span class="text-gray-500">{{ __('(indien gekend)') }}</span>
                    </label>
                    <input id="bf-date" type="date" wire:model="event_date" class="{{ $inputClass }} [color-scheme:dark]">
                    @error('event_date') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="bf-type" class="mb-1.5 block text-sm font-medium text-gray-200">{{ __('Type gelegenheid') }}</label>
                    <select id="bf-type" wire:model="event_type" class="{{ $inputClass }}">
                        <option value="">{{ __('Kies…') }}</option>
                        @foreach ($this->eventTypes() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('event_type') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="bf-location" class="mb-1.5 block text-sm font-medium text-gray-200">
                        {{ __('Locatie') }} <span class="text-gray-500">{{ __('(optioneel)') }}</span>
                    </label>
                    <input id="bf-location" type="text" wire:model="location" placeholder="{{ __('Zaal of stad') }}" class="{{ $inputClass }}">
                    @error('location') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="bf-guests" class="mb-1.5 block text-sm font-medium text-gray-200">
                        {{ __('Aantal gasten') }} <span class="text-gray-500">{{ __('(ca.)') }}</span>
                    </label>
                    <input id="bf-guests" type="text" wire:model="guests" placeholder="{{ __('bv. 150') }}" class="{{ $inputClass }}">
                    @error('guests') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="bf-message" class="mb-1.5 block text-sm font-medium text-gray-200">
                    {{ __('Extra info') }} <span class="text-gray-500">{{ __('(optioneel)') }}</span>
                </label>
                <textarea id="bf-message" rows="4" wire:model="message" placeholder="{{ __('Vertel me meer over je feest…') }}" class="{{ $inputClass }}"></textarea>
                @error('message') <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit" class="btn-primary w-full sm:w-auto">
                <span wire:loading.remove wire:target="submit" class="inline-flex items-center gap-2">
                    {{ __('Vraag offerte aan') }}
                    <x-lucide-send class="h-4 w-4" />
                </span>
                <span wire:loading wire:target="submit">{{ __('Versturen…') }}</span>
            </button>
        </form>
    @endif
</div>
