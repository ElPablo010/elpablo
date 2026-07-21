@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $layout = $content['form_layout'] ?? 'right';
    $formType = $content['form_type'] ?? 'contact';

    // form_type → Livewire-component. Voeg hier nieuwe types toe (zie FormFields).
    $formComponent = match ($formType) {
        'booking' => 'forms.booking-form',
        'contact' => 'forms.contact-form',
        default => null,
    };

    $isStacked = $layout === 'below';
    $textOrder = $layout === 'left' ? 'lg:order-2' : 'lg:order-1';
    $formOrder = $layout === 'left' ? 'lg:order-1' : 'lg:order-2';
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 py-24 lg:px-6">
        <div class="grid gap-12 @unless ($isStacked) lg:grid-cols-2 lg:items-start @endunless">
            <div @unless ($isStacked) class="{{ $textOrder }}" @endunless>
                <div class="flex items-center gap-3">
                    @if (! empty($content['number']))
                        <span class="font-display text-xl text-primary-500/80">{{ $content['number'] }}</span>
                    @endif
                    @if (! empty($content['eyebrow']))
                        <span class="eyebrow">{{ $content['eyebrow'] }}</span>
                    @endif
                </div>
                @if (! empty($content['heading']))
                    <h2 class="mt-4 font-display text-[2rem] leading-[0.95] text-white break-words sm:text-5xl">{{ $content['heading'] }}</h2>
                @endif
                @if (! empty($content['intro']))
                    <div class="prose-invert-brand mt-5 text-lg leading-relaxed">{!! $content['intro'] !!}</div>
                @endif
            </div>

            <div @unless ($isStacked) class="{{ $formOrder }}" @endunless>
                <div class="rounded-2xl border border-white/10 bg-ink-900 p-6 sm:p-8">
                    @if ($formComponent)
                        @livewire($formComponent)
                    @else
                        <p class="text-sm text-red-400">Onbekend formuliertype: {{ $formType }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-site.sections.wrapper>
