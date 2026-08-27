<x-layouts.site
    :title="$seo['title']"
    :description="$seo['description']"
    :canonical="$seo['canonical']"
    :robots="$seo['robots']"
    :image="$seo['image']"
    :image-alt="$seo['imageAlt']"
    :image-width="$seo['imageWidth']"
    :image-height="$seo['imageHeight']"
    :type="$seo['type']"
    :schema="$seo['schema']"
    :locale="$seo['locale']"
    :alternates="$seo['alternates']"
>
    @php $src = $mixtape->resolvedAudioUrl(); @endphp

    <section class="relative overflow-hidden bg-ink-950">
        <div class="pointer-events-none absolute -top-32 -left-24 h-96 w-96 rounded-full bg-primary-600/20 blur-3xl"></div>

        <div class="relative mx-auto w-full max-w-7xl px-4 pb-24 pt-36 sm:pt-44 lg:px-6">
            <a href="{{ \App\Support\Locale::href('/muziek') }}"
               class="inline-flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-gray-400 transition-colors hover:text-primary-500">
                <x-lucide-arrow-left class="h-4 w-4" />
                {{ __('Alle mixtapes') }}
            </a>

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
                class="mt-10 grid gap-10 lg:grid-cols-[24rem_1fr] lg:gap-16"
            >
                {{-- Cover met play/pause-overlay --}}
                <button
                    type="button"
                    @click="toggle()"
                    class="group relative block aspect-square w-full max-w-sm cursor-pointer overflow-hidden rounded-2xl border border-white/10 bg-ink-800"
                    :aria-label="playing ? '{{ __('Pauzeer') }}' : '{{ __('Speel af') }}'"
                >
                    @if (! empty($mixtape->cover_url))
                        <picture>
                            <source srcset="{{ $mixtape->cover_url }}" type="image/webp">
                            <img src="{{ $mixtape->cover_url }}" alt="{{ $mixtape->title }}" fetchpriority="high"
                                 class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                        </picture>
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-ink-950/80 via-transparent to-transparent"></div>
                    <span class="absolute inset-0 flex items-center justify-center">
                        <span class="flex h-20 w-20 items-center justify-center rounded-full bg-primary-600 text-white shadow-lg shadow-primary-600/40 transition-transform duration-300 group-hover:scale-110">
                            <x-lucide-play x-show="! playing" class="h-8 w-8 translate-x-0.5 fill-current" />
                            <x-lucide-pause x-show="playing" x-cloak class="h-8 w-8 fill-current" />
                        </span>
                    </span>
                </button>

                {{-- Info + speler --}}
                <div class="min-w-0 self-center">
                    <p class="eyebrow mb-5">Mixtape</p>
                    <h1 class="font-display text-[2.4rem] leading-[0.95] text-white break-words sm:text-5xl lg:text-6xl">{{ $mixtape->title }}</h1>
                    @if (filled($mixtape->subtitle))
                        <p class="mt-4 text-lg text-gray-400">{{ $mixtape->subtitle }}</p>
                    @endif

                    <audio
                        x-ref="audio"
                        src="{{ $src }}"
                        preload="metadata"
                        class="hidden"
                        @play="playing = true"
                        @pause="playing = false"
                        @ended="playing = false; progress = 0; current = '0:00'"
                        @timeupdate="progress = $refs.audio.duration ? ($refs.audio.currentTime / $refs.audio.duration * 100) : 0; current = fmt($refs.audio.currentTime)"
                        @loadedmetadata="duration = fmt($refs.audio.duration)"
                    ></audio>

                    {{-- Voortgangsbalk (klik om te spoelen) --}}
                    <div class="mt-8 max-w-xl">
                        <div @click="seek($event)" class="group h-1.5 w-full cursor-pointer rounded-full bg-white/10">
                            <div class="h-full rounded-full bg-primary-500" style="width: 0%" :style="`width: ${progress}%`"></div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                            <span x-text="current">0:00</span>
                            <span x-text="duration">0:00</span>
                        </div>
                    </div>

                    {{-- Bediening --}}
                    <div class="mt-6 flex flex-wrap items-center gap-4">
                        <button
                            type="button"
                            @click="toggle()"
                            class="btn-primary cursor-pointer"
                        >
                            <x-lucide-play x-show="! playing" class="h-4 w-4 fill-current" />
                            <x-lucide-pause x-show="playing" x-cloak class="h-4 w-4 fill-current" />
                            <span x-text="playing ? '{{ __('Pauze') }}' : '{{ __('Afspelen') }}'">{{ __('Afspelen') }}</span>
                        </button>

                        @if ($mixtape->allow_download && $src)
                            <a
                                href="{{ $src }}"
                                download
                                class="flex items-center gap-1.5 text-sm font-medium text-gray-400 transition-colors hover:text-primary-400"
                            >
                                <x-lucide-download class="h-4 w-4" />
                                Download
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
