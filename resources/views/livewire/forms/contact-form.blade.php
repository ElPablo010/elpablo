<div>
    {{-- Neutrale placeholder-opmaak (publieke Tailwind). Per project vrij te
         herontwerpen in design-website; de Livewire-logica blijft gelijk. --}}
    @if ($sent)
        <div class="rounded-xl bg-green-50 p-6 text-green-800" role="status">
            <p class="font-medium">Bedankt voor je bericht!</p>
            <p class="mt-1 text-sm">We nemen zo snel mogelijk contact met je op.</p>
        </div>
    @else
        <form wire:submit="submit" class="space-y-4">
            {{-- Honeypot — verborgen voor mensen, ingevuld door bots. --}}
            <div class="hidden" aria-hidden="true">
                <label>Laat dit veld leeg
                    <input type="text" wire:model="website" tabindex="-1" autocomplete="off">
                </label>
            </div>

            <div>
                <label for="cf-name" class="mb-1 block text-sm font-medium">Naam</label>
                <input id="cf-name" type="text" wire:model="name"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary-500 focus:ring-primary-500">
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="cf-email" class="mb-1 block text-sm font-medium">E-mail</label>
                    <input id="cf-email" type="email" wire:model="email"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary-500 focus:ring-primary-500">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cf-phone" class="mb-1 block text-sm font-medium">
                        Telefoon <span class="opacity-60">(optioneel)</span>
                    </label>
                    <input id="cf-phone" type="tel" wire:model="phone"
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary-500 focus:ring-primary-500">
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="cf-message" class="mb-1 block text-sm font-medium">Bericht</label>
                <textarea id="cf-message" rows="5" wire:model="message"
                          class="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:border-primary-500 focus:ring-primary-500"></textarea>
                @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                    class="inline-flex items-center rounded-full bg-primary-600 px-6 py-2.5 text-sm font-medium text-white cursor-pointer hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove wire:target="submit">Versturen</span>
                <span wire:loading wire:target="submit">Versturen…</span>
            </button>
        </form>
    @endif
</div>
