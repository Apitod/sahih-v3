@props([
    'label',
    'nilai',
    'catatan' => null,
    'ikon' => null,
    'tone' => 'netral',
])

@php
    $garis = [
        'netral' => 'border-garis',
        'sukses' => 'border-hijau/40',
        'proses' => 'border-amber/50',
        'bahaya' => 'border-bahaya-garis',
        'info' => 'border-sky-200',
    ][$tone] ?? 'border-garis';
@endphp

<div {{ $attributes->merge(['class' => "rounded-kartu border $garis bg-kertas p-4"]) }}>
    <div class="flex items-start justify-between gap-2">
        <p class="text-sm text-tinta/60">{{ $label }}</p>
        @if ($ikon)
            <span aria-hidden="true" class="text-base leading-none opacity-60">{{ $ikon }}</span>
        @endif
    </div>
    <p class="mt-1 font-display text-3xl tabular-nums">{{ $nilai }}</p>
    @if ($catatan)
        <p class="mt-1 text-xs text-tinta/50">{{ $catatan }}</p>
    @endif
</div>
