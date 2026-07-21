@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);
    $items = $content['items'] ?? [];
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-3xl px-4 py-24 lg:px-6">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :number="$content['number'] ?? null"
        />

        <div class="mt-12 space-y-3" x-data="{ open: 0 }">
            @foreach ($items as $i => $item)
                <div class="overflow-hidden rounded-xl border border-white/10 bg-ink-900">
                    <button
                        type="button"
                        @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="flex w-full cursor-pointer items-center justify-between gap-4 px-6 py-5 text-left"
                    >
                        <span class="font-semibold text-white">{{ $item['question'] ?? '' }}</span>
                        <x-lucide-plus class="h-5 w-5 shrink-0 text-primary-500 transition-transform duration-300" ::class="open === {{ $i }} ? 'rotate-45' : ''" />
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse x-cloak>
                        <div class="prose-invert-brand px-6 pb-5 text-sm leading-relaxed">{!! $item['answer'] ?? '' !!}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-site.sections.wrapper>
