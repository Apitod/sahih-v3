@extends('layouts.dasar')

@section('judul', 'Beranda — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-anggota />
@endsection

@section('topbar')
    {{-- Page title --}}
    <div>
        <h1 class="text-xl font-bold text-slate-900">Dashboard</h1>
        <p class="text-sm text-slate-600">Selamat datang kembali, {{ $member->name }}</p>
    </div>

    {{-- Quick stats badges --}}
    <div class="flex items-center gap-3">
        @php
            $rankLabels = ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'];
            $rankColors = [
                'reseller' => 'bg-slate-100 text-slate-700',
                'agent' => 'bg-blue-100 text-blue-700',
                'distributor' => 'bg-amber-100 text-amber-700',
                'manager' => 'bg-emerald-100 text-emerald-700',
            ];
        @endphp
        <span class="inline-flex items-center gap-1.5 rounded-lg {{ $rankColors[$member->rank] ?? 'bg-slate-100 text-slate-700' }} px-3 py-1.5 text-sm font-semibold">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
            </svg>
            {{ $rankLabels[$member->rank] ?? ucfirst($member->rank) }}
        </span>
    </div>
@endsection

@section('mobile-nav')
    {{-- Mobile nav already in nav-anggota component --}}
@endsection

@section('isi')
    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
            <div class="flex items-start gap-2">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    {{ session('status') }}
                    @if (session('status_trx'))
                        <span class="text-emerald-700"> • {{ session('status_trx') }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Stats Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            // Kelas ditulis utuh: Tailwind tidak bisa membaca nama kelas yang dirakit dari variabel.
            $stats = [
                ['label' => 'Poin Afiliasi', 'value' => $poin['pa'], 'desc' => 'Dari jaringan', 'bg' => 'bg-blue-100', 'fg' => 'text-blue-600'],
                ['label' => 'Poin Reward', 'value' => $poin['pr'], 'desc' => 'Dari belanja ulang', 'bg' => 'bg-emerald-100', 'fg' => 'text-emerald-600'],
                ['label' => 'Poin Leadership', 'value' => $poin['pl'], 'desc' => 'Dari kepemimpinan', 'bg' => 'bg-amber-100', 'fg' => 'text-amber-600'],
                ['label' => 'Hadiah Menunggu', 'value' => $status['menunggu_klaim'], 'desc' => 'Perlu diambil', 'bg' => 'bg-purple-100', 'fg' => 'text-purple-600'],
            ];
        @endphp

        @foreach ($stats as $stat)
            <div class="stat-card">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-600">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">
                            {{ number_format($stat['value'], 0, ',', '.') }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">{{ $stat['desc'] }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg {{ $stat['bg'] }}">
                        <svg class="h-6 w-6 {{ $stat['fg'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            @if ($stat['label'] === 'Poin Afiliasi')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            @elseif ($stat['label'] === 'Poin Reward')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            @elseif ($stat['label'] === 'Poin Leadership')
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                            @endif
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Progress Card --}}
    @if ($status['progres'])
        @php $p = $status['progres']; @endphp
        <div class="mt-6 rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-blue-100/50 p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $p['label'] }}</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ number_format($p['sekarang'], 0, ',', '.') }} / {{ number_format($p['target'], 0, ',', '.') }} {{ $p['satuan'] }}
                    </p>
                    <div class="mt-3 h-3 w-full overflow-hidden rounded-full bg-white/80" role="progressbar"
                         aria-valuenow="{{ $p['persen'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $p['label'] }}">
                        <div class="h-full rounded-full bg-blue-600 transition-all duration-500" style="width: {{ $p['persen'] }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-600">
                        @if ($p['sisa'] > 0)
                            Kurang {{ number_format($p['sisa'], 0, ',', '.') }} {{ $p['satuan'] }} lagi
                        @else
                            ✓ Syarat terpenuhi
                        @endif
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-4xl font-bold text-blue-600">{{ $p['persen'] }}%</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Referral Card --}}
    <div class="mt-6 rounded-lg border border-garis bg-krem p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-slate-900">Kode Referral Anda</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Bagikan kode ini ke calon anggota. Mereka menuliskannya di kolom
                    <span class="font-medium">Kode Referral</span> saat mendaftar di halaman daftar.
                </p>
            </div>
            <div class="shrink-0 rounded-lg border border-garis bg-white px-5 py-3 text-center">
                <p class="text-xs uppercase tracking-wide text-slate-500">Kode</p>
                <p class="mt-1 font-mono text-xl font-bold text-slate-900 select-all">{{ $member->username }}</p>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">
            Calon anggota yang memakai kode ini otomatis masuk jaringan Anda dan bonus sponsor
            dihitung sesuai peringkat Anda saat mereka mendaftar.
        </p>
    </div>

    {{-- Point History --}}
    <div class="mt-8">
        <h2 class="text-lg font-bold text-slate-900">Riwayat Poin</h2>
        @if ($riwayat->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="mt-2 text-sm font-medium text-slate-700">Belum ada poin tercatat</p>
                <p class="mt-1 text-sm text-slate-500">Poin muncul setelah PIN ditukar atau transaksi tercatat</p>
            </div>
        @else
            <div class="mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Jenis</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Poin</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($riwayat as $baris)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $baris->created_at?->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ $baris->kind === 'pa' ? 'bg-blue-100 text-blue-700' : ($baris->kind === 'pr' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ ['pa' => 'Poin Afiliasi', 'pr' => 'Poin Reward', 'pl' => 'Poin Leadership'][$baris->kind] ?? strtoupper($baris->kind) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold {{ $baris->delta > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $baris->delta > 0 ? '+' : '' }}{{ number_format($baris->delta, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $baris->note ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-paginasi :paginator="$riwayat" label="baris poin" />
        @endif
    </div>

    {{-- Milestone berikutnya --}}
    @if ($status['milestone'])
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-5 py-4">
            <div>
                <p class="text-sm font-semibold text-amber-900">Hadiah berikutnya: {{ $status['milestone']->name }}</p>
                <p class="text-xs text-amber-800/80">
                    Capai {{ number_format($status['milestone']->threshold, 0, ',', '.') }} poin total.
                    Sekarang {{ number_format($status['total_poin'], 0, ',', '.') }} poin.
                </p>
            </div>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                kurang {{ number_format(max(0, $status['milestone']->threshold - $status['total_poin']), 0, ',', '.') }} poin
            </span>
        </div>
    @endif

    {{-- Hadiah --}}
    <div class="mt-8" id="hadiah">
        <h2 class="text-lg font-bold text-slate-900">Hadiah yang jadi hak Anda</h2>
        @if ($hadiah->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada hadiah yang tercapai</p>
                <p class="mt-1 text-sm text-slate-500">Hadiah terbuka saat total poin melewati ambang tertentu.</p>
            </div>
        @else
            <ul class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                @foreach ($hadiah as $klaim)
                    @php
                        $kelasKlaim = match ($klaim->status) {
                            'eligible' => 'bg-amber-100 text-amber-800',
                            'requested' => 'bg-blue-100 text-blue-800',
                            'handed' => 'bg-emerald-100 text-emerald-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            default => 'bg-slate-100 text-slate-700',
                        };
                        $teksKlaim = match ($klaim->status) {
                            'eligible' => 'jadi hak Anda',
                            'requested' => 'sedang diproses',
                            'handed' => 'sudah diserahkan',
                            'rejected' => 'ditolak',
                            default => $klaim->status,
                        };
                    @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $klaim->reward?->name ?? 'Hadiah' }}</p>
                            <p class="text-slate-600">
                                ambang {{ number_format($klaim->reward?->threshold ?? 0, 0, ',', '.') }} poin · putaran {{ $klaim->cycle }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $kelasKlaim }}">{{ $teksKlaim }}</span>
                            @if ($klaim->status === 'eligible')
                                <form method="POST" action="{{ route('hadiah.klaim') }}">
                                    @csrf
                                    <input type="hidden" name="claim_id" value="{{ $klaim->id }}">
                                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">Ajukan</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-slate-500">Klaim diambil di kantor dengan menunjukkan bukti. Bawa nama hadiah dan ambangnya.</p>
            <x-paginasi :paginator="$hadiah" label="hadiah" />
        @endif
    </div>

    {{-- Cek Bonus — tanpa nominal rupiah (PRD v3.0 §3.1) --}}
    <div class="mt-8" id="bonus">
        <h2 class="text-lg font-bold text-slate-900">Cek Bonus</h2>
        @if ($bonus->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada bonus yang masuk</p>
                <p class="mt-1 text-sm text-slate-500">Bonus sponsor muncul setelah ada anggota di jaringan Anda yang bertransaksi.</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <caption class="sr-only">Rincian bonus sponsor yang masuk ke akun Anda</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Jenis</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Persen</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Posisi</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($bonus as $baris)
                            @php
                                $kelasBonus = match ($baris->status) {
                                    'available' => 'bg-emerald-100 text-emerald-800',
                                    'pending' => 'bg-amber-100 text-amber-800',
                                    'withdrawn' => 'bg-blue-100 text-blue-800',
                                    'void' => 'bg-red-100 text-red-800',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                                $teksBonus = match ($baris->status) {
                                    'available' => 'tersedia',
                                    'pending' => 'menunggu',
                                    'withdrawn' => 'sudah dicairkan',
                                    'void' => 'dibatalkan',
                                    default => $baris->status,
                                };
                                $jenisBonus = match ($baris->kind) {
                                    'sponsor' => 'bonus sponsor',
                                    'differential' => 'selisih peringkat',
                                    'leadership' => 'leadership',
                                    'rm' => 'royal manager',
                                    default => $baris->kind,
                                };
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ ($baris->available_at ?? $baris->created_at)?->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3">{{ $jenisBonus }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    {{ $baris->percent_bp !== null ? rtrim(rtrim(number_format($baris->percent_bp / 100, 2, ',', '.'), '0'), ',').'%' : '—' }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $baris->level !== null ? 'level '.$baris->level : '—' }}</td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $kelasBonus }}">{{ $teksBonus }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="mt-2 text-xs text-slate-500">Bonus dicairkan lewat admin. Halaman ini hanya menampilkan persen dan statusnya.</p>
            <x-paginasi :paginator="$bonus" label="baris bonus" />
        @endif
    </div>

    {{-- Network Tree --}}
    <div class="mt-8">
        <h2 class="text-lg font-bold text-slate-900">Pohon Genealogi</h2>
        @if ($pohon->isNotEmpty())
            <div class="network-tree mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white p-6">
                <ul class="tree-root">
                    <li class="tree-node">
                        <div class="tree-card tree-card-root">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-emerald-700 text-sm font-bold text-white">
                                {{ strtoupper(mb_substr($member->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $member->name }}</p>
                                <p class="truncate text-xs text-slate-600">{{ $member->username }}</p>
                            </div>
                            @php
                                $rankBadgeColors = [
                                    'reseller' => 'rank-reseller',
                                    'agent' => 'rank-agent',
                                    'distributor' => 'rank-distributor',
                                    'manager' => 'rank-manager',
                                ];
                            @endphp
                            <span class="rank-badge {{ $rankBadgeColors[$member->rank] ?? 'rank-reseller' }}">
                                {{ ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'][$member->rank] ?? ucfirst($member->rank) }}
                            </span>
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
            <p class="mt-2 text-xs text-slate-500">Ditampilkan sampai 3 tingkat. Hubungi admin untuk susunan lengkap.</p>
        @else
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada anggota di jaringan Anda</p>
                <p class="mt-1 text-sm text-slate-500">Ajak orang bergabung menggunakan link referral Anda.</p>
            </div>
        @endif
    </div>
@endsection
