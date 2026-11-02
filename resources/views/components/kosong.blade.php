@props(['judul', 'pesan' => null, 'aksi' => null, 'href' => null])

<div class="rounded-kartu border border-dashed border-garis bg-kertas/60 px-4 py-8 text-center">
    <p class="font-medium text-tinta/70">{{ $judul }}</p>
    @if ($pesan)
        <p class="mx-auto mt-1 max-w-md text-sm text-tinta/50">{{ $pesan }}</p>
    @endif
    @if ($aksi && $href)
        <a href="{{ $href }}"
           class="mt-4 inline-block rounded-kartu bg-amber px-4 py-2 text-sm font-medium text-tinta hover:bg-amber/90">
            {{ $aksi }}
        </a>
    @endif
</div>
