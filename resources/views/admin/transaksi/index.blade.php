@extends('layouts.dasar')

@section('judul', 'Transaksi — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Transaksi</p>
    </div>
@endsection

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Transaksi</h1>
            <p class="mt-1 text-sm text-slate-600">Pendaftaran, naik peringkat, dan repeat order</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.transaksi.index') }}" class="mt-4 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
        <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jenis</span>
            <select name="jenis" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                <option value="">Semua</option>
                @foreach (['join' => 'Pendaftaran', 'upgrade' => 'Upgrade', 'ro' => 'Repeat Order'] as $val => $label)
                    <option value="{{ $val }}" @selected(request('jenis') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Anggota</span>
            <input type="text" name="anggota" value="{{ request('anggota') }}" placeholder="username"
                   class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </label>
        <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dari</span>
            <input type="date" name="dari" value="{{ request('dari') }}"
                   class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </label>
        <label class="block">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sampai</span>
            <input type="date" name="sampai" value="{{ request('sampai') }}"
                   class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
        </label>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Saring</button>
            <a href="{{ route('admin.transaksi.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <caption class="sr-only">Daftar transaksi</caption>
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Kode</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Anggota</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Jenis</th>
                    <th scope="col" class="px-4 py-3 text-right font-semibold">Nilai</th>
                    <th scope="col" class="px-4 py-3 text-right font-semibold">Bonus Sponsor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($transaksi as $t)
                    <tr class="cursor-pointer transition hover:bg-slate-50"
                        onclick="window.location='{{ route('admin.transaksi.show', $t) }}'">
                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-600">{{ $t->posted_at?->format('j M Y') ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs">
                            <a href="{{ route('admin.transaksi.show', $t) }}" class="text-blue-700 underline decoration-blue-200 underline-offset-2 hover:text-blue-900">{{ $t->code }}</a>
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $t->member?->username ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ match ($t->type) {
                                'join' => 'bg-blue-100 text-blue-700',
                                'upgrade' => 'bg-amber-100 text-amber-700',
                                'ro' => 'bg-emerald-100 text-emerald-700',
                                default => 'bg-slate-100 text-slate-700',
                            } }}">{{ $t->type }}</span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums font-semibold">Rp {{ number_format((int) $t->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-600">Rp {{ number_format((int) $t->sponsor_bonus_total, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada transaksi pada filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $transaksi->withQueryString()->links() }}</div>
@endsection
