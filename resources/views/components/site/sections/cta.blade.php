@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? 'primary');
    $isPrimary = ($content['background'] ?? 'primary') === 'primary';
    $ctas = $content['ctas'] ?? [];

    // Op een magenta CTA-vlak: wit-op-magenta knop. Anders de merk-varianten.
    $btnClass = function (?string $v) use ($isPrimary) {
        if ($isPrimary) {
            return $v === 'secondary' || $v === 'ghost'
                ? 'btn border border-white/40 text-white hover:bg-white/10'
                : 'btn bg-white text-primary-700 hover:bg-white/90';
        }
        return match ($v) {
            'secondary' => 'btn-secondary',
            'ghost' => 'btn-ghost',
            default => 'btn-primary',
        };
    };
@endphp

<x-site.sections.wrapper :content="$content" class="relative overflow-hidden {{ $bg }}">
    @if ($isPrimary)
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-primary-500 to-primary-700"></div>
        <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-white/10 blur-3xl"></div>
    @endif

    <div class="relative mx-auto max-w-3xl px-4 py-24 text-center lg:px-6">
        @if (! empty($content['eyebrow']))
            <p class="mb-4 text-xs font-semibold uppercase tracking-[0.2em] {{ $isPrimary ? 'text-white/80' : 'text-primary-500' }}">{{ $content['eyebrow'] }}</p>
        @endif
        @if (! empty($content['heading']))
            <h2 class="font-display text-[2rem] leading-[0.95] text-white break-words sm:text-6xl">{{ $content['heading'] }}</h2>
        @endif
        @if (! empty($content['intro']))
            <div class="mx-auto mt-5 max-w-xl text-lg leading-relaxed {{ $isPrimary ? 'text-white/90' : 'text-gray-300' }}">{!! $content['intro'] !!}</div>
        @endif

        @if (! empty($ctas))
            <div class="mt-9 flex flex-wrap justify-center gap-4">
                @foreach ($ctas as $cta)
                    <a href="{{ \App\Support\Locale::href($cta['href'] ?? '/') }}" class="{{ $btnClass($cta['variant'] ?? 'primary') }}">
                        {{ $cta['label'] ?? '' }}
                        <x-lucide-arrow-right class="h-4 w-4" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-site.sections.wrapper>
