@props(['paginator', 'label' => 'data'])

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman {{ $label }}"
         class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-tinta/60">
            Menampilkan
            <span class="font-medium tabular-nums text-tinta">{{ $paginator->firstItem() }}</span>&ndash;<span class="font-medium tabular-nums text-tinta">{{ $paginator->lastItem() }}</span>
            dari <span class="font-medium tabular-nums text-tinta">{{ $paginator->total() }}</span> {{ $label }}
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="rounded-kartu border border-garis px-2.5 py-1 text-tinta/30">&laquo; Sebelumnya</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="rounded-kartu border border-garis px-2.5 py-1 hover:bg-krem">&laquo; Sebelumnya</a>
            @endif

            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $halaman => $url)
                @if ($halaman === $paginator->currentPage())
                    <span aria-current="page"
                          class="rounded-kartu bg-amber px-2.5 py-1 font-semibold tabular-nums text-tinta">{{ $halaman }}</span>
                @else
                    <a href="{{ $url }}"
                       class="rounded-kartu border border-garis px-2.5 py-1 tabular-nums hover:bg-krem">{{ $halaman }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="rounded-kartu border border-garis px-2.5 py-1 hover:bg-krem">Berikutnya &raquo;</a>
            @else
                <span aria-disabled="true" class="rounded-kartu border border-garis px-2.5 py-1 text-tinta/30">Berikutnya &raquo;</span>
            @endif
        </div>
    </nav>
@elseif ($paginator->total() > 0)
    <p class="mt-4 text-xs text-tinta/50">
        Menampilkan seluruh {{ number_format($paginator->total(), 0, ',', '.') }} {{ $label }}.
    </p>
@endif
