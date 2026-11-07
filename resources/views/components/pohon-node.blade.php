{{-- Satu simpul pada pohon genealogi. Dipanggil berulang untuk anak-anaknya. --}}
@props(['simpul', 'labelPeringkat' => []])

@php $a = $simpul['model']; @endphp

<li class="tree-node">
    <div class="tree-card">
        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-700" aria-hidden="true">
            {{ strtoupper(mb_substr($a->name, 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900">{{ $a->name }}</p>
            <p class="truncate text-xs text-slate-600">{{ $a->username }}</p>
        </div>
        <span class="rank-badge rank-{{ $a->rank }}">
            {{ $labelPeringkat[$a->rank] ?? ucfirst($a->rank) }}
        </span>
        {{-- Status akun: tanpa ini admin/anggota tidak tahu ada downline yang dibekukan. --}}
        <span data-status="{{ $a->status }}"
              class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium {{ $a->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
            {{ $a->status === 'active' ? 'Aktif' : 'Beku' }}
        </span>
    </div>

    @if ($simpul['anak']->isNotEmpty())
        <ul class="tree-branch">
            @foreach ($simpul['anak'] as $anak)
                <x-pohon-node :simpul="$anak" :labelPeringkat="$labelPeringkat" />
            @endforeach
        </ul>
    @endif
</li>
