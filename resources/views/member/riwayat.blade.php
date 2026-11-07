@extends('layouts.dasar')

@section('judul', 'Riwayat — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-anggota />
@endsection

@section('topbar')
    <div>
        <h1 class="text-xl font-bold text-slate-900">Riwayat</h1>
        <p class="text-sm text-slate-600">Poin dan transaksi</p>
    </div>
@endsection

@section('isi')
    {{-- Riwayat Poin --}}
    <section aria-labelledby="judul-poin">
        <h2 id="judul-poin" class="text-lg font-bold text-slate-900">Riwayat Poin</h2>
        @if ($poin->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada riwayat poin</p>
                <p class="mt-1 text-sm text-slate-500">Riwayat muncul setelah transaksi pertama.</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <caption class="sr-only">Riwayat poin anggota</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Jenis</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Poin</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($poin as $p)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $p->created_at->translatedFormat('j M Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ match ($p->kind) {
                                        'pa' => 'bg-blue-100 text-blue-700',
                                        'pr' => 'bg-emerald-100 text-emerald-700',
                                        'pl' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    } }}">{{ strtoupper($p->kind) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold {{ $p->delta >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                    {{ $p->delta >= 0 ? '+' : '' }}{{ number_format($p->delta, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $p->note ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-paginasi :paginator="$poin" label="poin" />
        @endif
    </section>

    {{-- Riwayat Transaksi --}}
    <section class="mt-8" aria-labelledby="judul-transaksi">
        <h2 id="judul-transaksi" class="text-lg font-bold text-slate-900">Riwayat Transaksi</h2>
        @if ($transaksi->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada transaksi</p>
                <p class="mt-1 text-sm text-slate-500">Riwayat muncul setelah Anda memakai PIN.</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <caption class="sr-only">Riwayat transaksi anggota</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Jenis</th>
                            <th scope="col" class="px-4 py-3 font-semibold">PIN</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Poin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($transaksi as $trx)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $trx->posted_at?->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ match ($trx->type) {
                                        'join' => 'bg-blue-100 text-blue-700',
                                        'upgrade' => 'bg-amber-100 text-amber-700',
                                        'ro' => 'bg-emerald-100 text-emerald-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    } }}">{{ match ($trx->type) {
                                        'join' => 'pendaftaran',
                                        'upgrade' => 'upgrade peringkat',
                                        'ro' => 'repeat order',
                                        default => $trx->type,
                                    } }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $trx->pin?->code ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ number_format($trx->points, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-paginasi :paginator="$transaksi" label="transaksi" />
        @endif
    </section>
@endsection
