@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $layout = $content['form_layout'] ?? 'right';
    $formType = $content['form_type'] ?? 'contact';

    // form_type → Livewire-component. Voeg hier nieuwe types toe (zie FormFields).
    $formComponent = match ($formType) {
        'contact' => 'forms.contact-form',
        default => null,
    };

    // 'below' = gestapeld (tekst boven, formulier onder). 'left'/'right' = twee
    // kolommen op md+, met de tekst respectievelijk rechts/links van het formulier.
    $isStacked = $layout === 'below';
    $textOrder = $layout === 'left' ? 'md:order-2' : 'md:order-1';
    $formOrder = $layout === 'left' ? 'md:order-1' : 'md:order-2';
@endphp

{{-- Neutrale placeholder. Per project vrij te herontwerpen. --}}
<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 py-20">
        <div class="grid gap-10 @unless ($isStacked) md:grid-cols-2 md:items-start @endunless">
            <div @unless ($isStacked) class="{{ $textOrder }}" @endunless>
                @if (! empty($content['eyebrow']))
                    <p class="mb-2 text-sm font-medium uppercase tracking-wide opacity-70">{{ $content['eyebrow'] }}</p>
                @endif
                @if (! empty($content['heading']))
                    <h2 class="text-3xl font-bold">{{ $content['heading'] }}</h2>
                @endif
                @if (! empty($content['intro']))
                    <div class="prose mt-4 max-w-none">{!! $content['intro'] !!}</div>
                @endif
            </div>

            <div @unless ($isStacked) class="{{ $formOrder }}" @endunless>
                @if ($formComponent)
                    @livewire($formComponent)
                @else
                    <p class="text-sm text-red-600">Onbekend formuliertype: {{ $formType }}</p>
                @endif
            </div>
        </div>
    </div>
</x-site.sections.wrapper>
