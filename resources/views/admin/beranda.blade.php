@extends('layouts.dasar')

@section('judul', 'Admin Dashboard — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <h1 class="text-xl font-bold text-slate-900">Dashboard Admin</h1>
        <p class="text-sm text-slate-600">Ringkasan kondisi keanggotaan, PIN, dan transaksi</p>
    </div>

    <div class="flex items-center gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-3 py-1.5 text-sm font-semibold text-emerald-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Sesi aktif
        </span>
        <span class="inline-flex rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700">
            {{ ucfirst($status['admin']->role) }}
        </span>
    </div>
@endsection

@section('mobile-nav')
    {{-- Mobile nav already in nav-admin component --}}
@endsection

@section('isi')
    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
            {{ session('status') }}
        </div>
    @endif

    {{-- Stats Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-slate-600">Total Anggota</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">
                        {{ number_format($jumlahAnggota, 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">{{ $status['anggota_aktif'] }} aktif · {{ $status['anggota_beku'] }} beku</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100">
                    <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-slate-600">PIN Tersedia</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">
                        {{ number_format($jumlahPinBelumDipakai, 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Siap dijual</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100">
                    <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-slate-600">Total Transaksi</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">
                        {{ number_format($jumlahTransaksi, 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Rp {{ number_format($status['nilai_transaksi'], 0, ',', '.') }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100">
                    <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-sm font-medium text-slate-600">PIN Dibatalkan</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">
                        {{ number_format($status['pin']['void'], 0, ',', '.') }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">{{ $status['pin']['used'] }} sudah dipakai</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Membership Status Card --}}
    <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-lg font-bold text-slate-900">Status Keanggotaan</h2>
            <p class="text-sm tabular-nums text-slate-600">
                {{ $status['persen_aktif'] }}% aktif dari {{ number_format($status['total_anggota'], 0, ',', '.') }} anggota
            </p>
        </div>

        <div class="mt-3 h-3 overflow-hidden rounded-full bg-slate-100" role="progressbar"
             aria-valuenow="{{ $status['persen_aktif'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Persentase anggota aktif">
            <div class="h-full rounded-full bg-emerald-600 transition-all duration-500" style="width: {{ $status['persen_aktif'] }}%"></div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                {{ $status['anggota_aktif'] }} aktif
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full {{ $status['anggota_beku'] > 0 ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700' }} px-3 py-1 text-xs font-semibold">
                <span class="h-2 w-2 rounded-full {{ $status['anggota_beku'] > 0 ? 'bg-red-600' : 'bg-slate-400' }}"></span>
                {{ $status['anggota_beku'] }} dibekukan
            </span>
            @if ($status['klaim_menunggu'] > 0)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                    <span class="h-2 w-2 rounded-full bg-amber-600"></span>
                    {{ $status['klaim_menunggu'] }} klaim menunggu
                </span>
            @endif
        </div>

        {{-- Rank Distribution --}}
        <div class="mt-6">
            <h3 class="text-sm font-semibold text-slate-700">Sebaran Peringkat</h3>
            @php
                $totalPeringkat = array_sum($status['peringkat']);
                $rankLabels = ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'];
                $rankColors = ['reseller' => 'bg-slate-500', 'agent' => 'bg-blue-500', 'distributor' => 'bg-amber-500', 'manager' => 'bg-emerald-500'];
            @endphp
            <ul class="mt-3 space-y-2">
                @foreach ($status['peringkat'] as $rank => $jumlah)
                    @php $persen = $totalPeringkat > 0 ? (int) round($jumlah / $totalPeringkat * 100) : 0; @endphp
                    <li class="flex items-center gap-3 text-sm">
                        <span class="w-24 shrink-0 text-slate-600">{{ $rankLabels[$rank] ?? ucfirst($rank) }}</span>
                        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
                            <div class="{{ $rankColors[$rank] ?? 'bg-slate-400' }} h-full rounded-full transition-all duration-500" style="width: {{ $persen }}%"></div>
                        </div>
                        <span class="w-16 shrink-0 text-right tabular-nums text-slate-600">{{ $jumlah }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Recent Transactions --}}
    <div class="mt-8">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <h2 class="text-lg font-bold text-slate-900">Transaksi Terakhir</h2>
            <a href="{{ route('admin.pin.index') }}" class="text-sm text-blue-600 hover:text-blue-700 hover:underline">Kelola PIN →</a>
        </div>

        @if ($transaksiTerakhir->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-12 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada transaksi</p>
                <p class="mt-1 text-sm text-slate-500">Transaksi tercatat saat anggota menukar PIN</p>
                <a href="{{ route('admin.pin.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Buat PIN
                </a>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <caption class="sr-only">Daftar transaksi terakhir</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Anggota</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Jenis</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Kode</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($transaksiTerakhir as $trx)
                            @php
                                $badgeClass = match ($trx->type) {
                                    'join' => 'bg-blue-100 text-blue-700',
                                    'upgrade' => 'bg-amber-100 text-amber-700',
                                    'ro' => 'bg-emerald-100 text-emerald-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                                $badgeLabel = match ($trx->type) {
                                    'join' => 'pendaftaran',
                                    'upgrade' => 'upgrade',
                                    'ro' => 'repeat order',
                                    default => ucfirst($trx->type),
                                };
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $trx->created_at?->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $trx->member?->username ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $trx->code }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-900">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-paginasi :paginator="$transaksiTerakhir" label="transaksi" />
        @endif
    </div>
@endsection
