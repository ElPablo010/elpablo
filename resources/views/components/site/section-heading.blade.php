@props([
    'eyebrow' => null,
    'heading' => null,
    'intro' => null,
    'number' => null,
    'align' => 'center', // center | left
])

@php
    $isCenter = $align === 'center';
@endphp

@if ($eyebrow || $heading || $intro)
    <div class="{{ $isCenter ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl' }}">
        <div class="flex items-center gap-3 {{ $isCenter ? 'justify-center' : '' }}">
            @if ($number)
                <span class="font-display text-xl text-primary-500/80">{{ $number }}</span>
            @endif
            @if ($eyebrow)
                <span class="eyebrow">{{ $eyebrow }}</span>
            @endif
        </div>

        @if ($heading)
            <h2 class="mt-4 font-display text-[2rem] leading-[0.95] text-white break-words sm:text-5xl">{{ $heading }}</h2>
        @endif

        @if ($intro)
            <div class="prose-invert-brand mt-4 text-lg leading-relaxed {{ $isCenter ? 'mx-auto' : '' }}">{!! $intro !!}</div>
        @endif
    </div>
@endif
