@extends('layouts.dasar')

@section('judul', $anggota->name.' — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Detail Anggota</p>
        <p class="text-base font-bold text-slate-900">{{ $anggota->username }}</p>
    </div>
    <a href="{{ route('admin.anggota.edit', $anggota) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
        Edit
    </a>
@endsection

@section('isi')
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Profil --}}
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-bold text-slate-900">Profil</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="font-medium text-slate-600">Username</dt>
                    <dd class="font-mono text-slate-900">{{ $anggota->username }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="font-medium text-slate-600">Nama</dt>
                    <dd class="text-slate-900">{{ $anggota->name }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="font-medium text-slate-600">NIK KTP</dt>
                    <dd class="text-slate-900">{{ $anggota->nik_ktp ?? '—' }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="font-medium text-slate-600">Telepon</dt>
                    <dd class="text-slate-900">{{ $anggota->phone }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="font-medium text-slate-600">Alamat</dt>
                    <dd class="text-slate-900">{{ $anggota->address ?? '—' }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="font-medium text-slate-600">Peringkat</dt>
                    <dd class="text-slate-900">{{ ucfirst($anggota->rank) }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="font-medium text-slate-600">Status</dt>
                    <dd>
                        <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ $anggota->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ $anggota->status === 'active' ? 'Aktif' : 'Dibekukan' }}
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="font-medium text-slate-600">Sponsor</dt>
                    <dd class="text-slate-900">{{ $anggota->sponsor?->username ?? '—' }}</dd>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <dt class="font-medium text-slate-600">Bergabung</dt>
                    <dd class="text-slate-900">{{ $anggota->joined_at?->translatedFormat('j M Y') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Stats --}}
        <div class="space-y-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-600">Poin PA</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-blue-900">{{ number_format($poin['pa'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-600">Poin PR</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-900">{{ number_format($poin['pr'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-600">Poin PL</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-amber-900">{{ number_format($poin['pl'], 0, ',', '.') }}</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-sm text-slate-600">Jaringan Langsung</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $anggota->downlines->count() }}</p>
            </div>
        </div>
    </div>

    {{-- Genealogi --}}
    <section class="mt-8">
        <h2 class="text-lg font-bold text-slate-900">Pohon Genealogi</h2>
        @if ($pohon->isNotEmpty())
            <div class="network-tree mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white p-6">
                <ul class="tree-root">
                    <li class="tree-node">
                        <div class="tree-card tree-card-root">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-blue-700 text-sm font-bold text-white">
                                {{ strtoupper(mb_substr($anggota->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $anggota->name }}</p>
                                <p class="truncate text-xs text-slate-600">{{ $anggota->username }}</p>
                            </div>
                            <span class="rank-badge rank-{{ $anggota->rank }}">{{ ucfirst($anggota->rank) }}</span>
                        </div>

                        @if ($pohon->isNotEmpty())
                            <ul class="tree-branch">
                                @foreach ($pohon as $simpul)
                                    <x-pohon-node :simpul="$simpul" :labelPeringkat="['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager']" />
                                @endforeach
                            </ul>
                        @endif
                    </li>
                </ul>
            </div>
            <p class="mt-2 text-xs text-slate-500">Ditampilkan sampai 3 tingkat.</p>
        @else
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada downline</p>
            </div>
        @endif
    </section>
@endsection
