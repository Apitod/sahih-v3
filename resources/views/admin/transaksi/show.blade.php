@extends('layouts.dasar')

@section('judul', 'Detail Transaksi — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Detail Transaksi</p>
    </div>
@endsection

@section('isi')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $transaksi->code }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $transaksi->posted_at?->format('j F Y H:i') ?? '—' }}</p>
        </div>
        <a href="{{ route('admin.transaksi.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Kembali</a>
    </div>

    <dl class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Anggota</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $transaksi->member?->username ?? '—' }}</dd>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $transaksi->type }}</dd>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nilai</dt>
            <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-900">Rp {{ number_format((int) $transaksi->amount, 0, ',', '.') }}</dd>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bonus Sponsor</dt>
            <dd class="mt-1 text-sm font-semibold tabular-nums text-slate-900">Rp {{ number_format((int) $transaksi->sponsor_bonus_total, 0, ',', '.') }}</dd>
        </div>
    </dl>

    <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <caption class="border-b border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-semibold text-slate-900">Bonus yang lahir dari transaksi ini</caption>
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-2 font-semibold">Anggota</th>
                    <th scope="col" class="px-4 py-2 font-semibold">Jenis</th>
                    <th scope="col" class="px-4 py-2 text-right font-semibold">Nominal</th>
                    <th scope="col" class="px-4 py-2 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transaksi->bonuses as $b)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $b->member?->username ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $b->kind }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $b->amount }}</td>
                        <td class="px-4 py-2">{{ $b->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Tidak ada bonus dari transaksi ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
