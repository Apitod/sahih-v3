@props(['judul', 'keterangan' => null])

<div class="flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="font-display text-2xl tracking-tight">{{ $judul }}</h1>
        @if ($keterangan)
            <p class="mt-1 text-sm text-tinta/70">{{ $keterangan }}</p>
        @endif
    </div>
    @isset($aksi)
        <div class="flex flex-wrap items-center gap-2">{{ $aksi }}</div>
    @endisset
</div>
