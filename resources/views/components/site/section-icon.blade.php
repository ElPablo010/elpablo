@props(['name' => null, 'size' => 'h-6 w-6'])

{{-- Lucide-icoon uit een vrij tekstveld in de admin. Een onbekende naam
     rendert niets in plaats van de pagina te breken. --}}
@php
    $icon = null;
    if (filled($name)) {
        try {
            $icon = svg('lucide-'.str_replace(['_', ' '], '-', strtolower(trim($name))), $size)->toHtml();
        } catch (\Throwable) {
            $icon = null;
        }
    }
@endphp

@if ($icon)
    <span {{ $attributes }}>{!! $icon !!}</span>
@endif
