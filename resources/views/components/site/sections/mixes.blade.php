@props(['section' => null, 'content' => []])

@php
    $bg = \App\Filament\Schemas\Sections\SectionBackground::classes($content['background'] ?? null);

    // Mixtapes zijn een eigen posttype (Website → Mixtapes). "Toon alle
    // mixtapes" volgt de versleepbare volgorde uit de admin; een handmatige
    // selectie behoudt de volgorde waarin de ids in de sectie staan.
    $showAll = (bool) ($content['show_all'] ?? true);
    $ids = array_map('intval', $content['mixtape_ids'] ?? []);

    $query = \App\Models\Mixtape::query()->published();

    $mixtapes = $showAll
        ? $query->ordered()->get()
        : $query->whereIn('id', $ids)->get()
            ->sortBy(fn ($mixtape) => array_search($mixtape->id, $ids))
            ->values();

    $ctas = $content['ctas'] ?? [];
    $btnClass = fn (?string $v) => match ($v) {
        'primary' => 'btn-primary',
        'ghost' => 'btn-ghost',
        default => 'btn-secondary',
    };
@endphp

<x-site.sections.wrapper :content="$content" class="{{ $bg }}">
    <div class="mx-auto max-w-7xl px-4 py-24 lg:px-6">
        <x-site.section-heading
            :eyebrow="$content['eyebrow'] ?? null"
            :heading="$content['heading'] ?? null"
            :intro="$content['intro'] ?? null"
            :number="$content['number'] ?? null"
        />

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($mixtapes as $mixtape)
                @php $src = $mixtape->resolvedAudioUrl(); @endphp
                <div
                    x-data="{
                        playing: false,
                        progress: 0,
                        current: '0:00',
                        duration: '0:00',
                        toggle() { const a = this.$refs.audio; a.paused ? a.play() : a.pause(); },
                        fmt(s) { if (!s || isNaN(s)) return '0:00'; const m = Math.floor(s/60); const sec = Math.floor(s%60).toString().padStart(2,'0'); return m+':'+sec; },
                        seek(e) { const a = this.$refs.audio; if (!a.duration) return; const r = e.currentTarget.getBoundingClientRect(); a.currentTime = ((e.clientX - r.left)/r.width) * a.duration; },
                    }"
                    @mix-play.window="if ($event.detail !== {{ $mixtape->id }} && ! $refs.audio.paused) $refs.audio.pause()"
                    class="flex flex-col overflow-hidden rounded-2xl border border-white/10 bg-ink-900"
                >
                    {{-- Cover + play/pause-overlay --}}
                    <button
                        type="button"
                        @click="toggle()"
                        class="group relative block aspect-square w-full cursor-pointer overflow-hidden bg-ink-800"
                        :aria-label="playing ? 'Pauzeer' : 'Speel af'"
                    >
                        @if (! empty($mixtape->cover_url))
                            <picture>
                                <source srcset="{{ $mixtape->cover_url }}" type="image/webp">
                                <img src="{{ $mixtape->cover_url }}" alt="{{ $mixtape->title }}" loading="lazy"
                                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                            </picture>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-ink-950/80 via-transparent to-transparent"></div>
                        <span class="absolute inset-0 flex items-center justify-center">
                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg shadow-primary-600/40 transition-transform duration-300 group-hover:scale-110">
                                <x-lucide-play x-show="! playing" class="h-6 w-6 translate-x-0.5 fill-current" />
                                <x-lucide-pause x-show="playing" x-cloak class="h-6 w-6 fill-current" />
                            </span>
                        </span>
                    </button>

                    {{-- Info + speler --}}
                    <div class="flex flex-1 flex-col p-6">
                        <h3 class="text-lg font-bold text-white">{{ $mixtape->title }}</h3>
                        @if (filled($mixtape->subtitle))
                            <p class="mt-1 text-sm text-gray-400">{{ $mixtape->subtitle }}</p>
                        @endif

                        <audio
                            x-ref="audio"
                            src="{{ $src }}"
                            preload="metadata"
                            class="hidden"
                            @play="playing = true; $dispatch('mix-play', {{ $mixtape->id }})"
                            @pause="playing = false"
                            @ended="playing = false; progress = 0; current = '0:00'"
                            @timeupdate="progress = $refs.audio.duration ? ($refs.audio.currentTime / $refs.audio.duration * 100) : 0; current = fmt($refs.audio.currentTime)"
                            @loadedmetadata="duration = fmt($refs.audio.duration)"
                        ></audio>

                        {{-- Voortgangsbalk (klik om te spoelen) --}}
                        <div class="mt-4">
                            <div @click="seek($event)" class="group h-1.5 w-full cursor-pointer rounded-full bg-white/10">
                                <div class="h-full rounded-full bg-primary-500" style="width: 0%" :style="`width: ${progress}%`"></div>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                                <span x-text="current">0:00</span>
                                <span x-text="duration">0:00</span>
                            </div>
                        </div>

                        {{-- Bediening --}}
                        <div class="mt-4 flex items-center gap-3">
                            <button
                                type="button"
                                @click="toggle()"
                                class="flex cursor-pointer items-center gap-2 rounded-full bg-white/5 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-white/10"
                            >
                                <x-lucide-play x-show="! playing" class="h-4 w-4 fill-current" />
                                <x-lucide-pause x-show="playing" x-cloak class="h-4 w-4 fill-current" />
                                <span x-text="playing ? 'Pauze' : 'Afspelen'">Afspelen</span>
                            </button>

                            @if ($mixtape->allow_download && $src)
                                <a
                                    href="{{ $src }}"
                                    download
                                    class="ml-auto flex items-center gap-1.5 text-sm font-medium text-gray-400 transition-colors hover:text-primary-400"
                                >
                                    <x-lucide-download class="h-4 w-4" />
                                    Download
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (! empty($ctas))
            <div class="mt-12 flex flex-wrap justify-center gap-4">
                @foreach ($ctas as $cta)
                    <a href="{{ \App\Support\Locale::href($cta['href'] ?? '/') }}" class="{{ $btnClass($cta['variant'] ?? 'secondary') }}">
                        {{ $cta['label'] ?? '' }}
                        <x-lucide-arrow-right class="h-4 w-4" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-site.sections.wrapper>
