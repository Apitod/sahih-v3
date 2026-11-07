@extends('layouts.dasar')

@section('judul', 'Finansial — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Finansial</p>
    </div>
@endsection

@section('isi')
    @php
        $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
        $selisih = $omsetBulanIni - $omsetBulanLalu;
    @endphp

    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Finansial</h1>
        <p class="mt-1 text-sm text-slate-600">Ringkasan omset, payout bonus, dan profit kotor</p>
    </div>

    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Omset</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $rp($omset) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Omset Bulan Ini</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $rp($omsetBulanIni) }}</p>
            <p class="mt-1 text-xs {{ $selisih >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                {{ $selisih >= 0 ? '▲' : '▼' }} {{ $rp(abs($selisih)) }} vs bulan lalu ({{ $rp($omsetBulanLalu) }})
            </p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Payout</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-slate-900">{{ $rp($payout) }}</p>
            <p class="mt-1 text-xs text-slate-500">Bulan ini: {{ $rp($payoutBulanIni) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Profit Kotor</p>
            <p class="mt-1 text-2xl font-bold tabular-nums {{ $profit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $rp($profit) }}</p>
            <p class="mt-1 text-xs text-slate-500">Margin {{ $margin }}% · omset − payout</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <caption class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-semibold text-slate-900">Payout per jenis bonus</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th scope="col" class="px-4 py-2 font-semibold">Jenis</th>
                        <th scope="col" class="px-4 py-2 text-right font-semibold">Entri</th>
                        <th scope="col" class="px-4 py-2 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach (['sponsor', 'differential', 'leadership', 'rm'] as $kind)
                        @php $row = $perKind->get($kind); @endphp
                        <tr>
                            <td class="px-4 py-2 font-medium capitalize">{{ $kind === 'rm' ? 'Royal Manager' : $kind }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $row?->jumlah ?? 0 }}</td>
                            <td class="px-4 py-2 text-right tabular-nums font-semibold">{{ $rp($row?->total ?? 0) }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-slate-50">
                        <td class="px-4 py-2 font-semibold">Saldo belum ditarik</td>
                        <td class="px-4 py-2"></td>
                        <td class="px-4 py-2 text-right tabular-nums font-semibold">{{ $rp($saldoBelumTarik) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <caption class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-semibold text-slate-900">Distribusi transaksi</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th scope="col" class="px-4 py-2 font-semibold">Jenis</th>
                        <th scope="col" class="px-4 py-2 text-right font-semibold">Jumlah</th>
                        <th scope="col" class="px-4 py-2 text-right font-semibold">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach (['join' => 'Pendaftaran', 'upgrade' => 'Upgrade', 'ro' => 'Repeat Order'] as $type => $label)
                        @php $row = $distribusi->get($type); @endphp
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $label }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ $row?->jumlah ?? 0 }}</td>
                            <td class="px-4 py-2 text-right tabular-nums font-semibold">{{ $rp($row?->total ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-4 text-xs text-slate-500">
        Profit = omset − payout (konsep, bukan akuntansi ketat). Payout menghitung bonus berstatus available dan withdrawn; pending dan void dikecualikan.
    </p>
@endsection
