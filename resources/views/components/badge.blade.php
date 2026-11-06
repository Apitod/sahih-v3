@props(['tone' => 'netral'])

@php
    // Nada warna dipusatkan di satu tempat supaya badge konsisten di semua halaman.
    $nada = [
        'netral' => 'bg-krem text-tinta/70 border-garis',
        'sukses' => 'bg-hijau/15 text-hijau-tua border-hijau/30',
        'proses' => 'bg-amber/20 text-amber-900 border-amber/40',
        'bahaya' => 'bg-bahaya-lembut text-bahaya border-bahaya-garis',
        'info' => 'bg-sky-50 text-sky-800 border-sky-200',
    ][$tone] ?? 'bg-krem text-tinta/70 border-garis';
@endphp

<span {{ $attributes->merge([
    'class' => "inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-xs font-medium whitespace-nowrap $nada",
]) }}>
    {{ $slot }}
</span>
