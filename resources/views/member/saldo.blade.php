@extends('layouts.dasar')

@section('judul', 'Saldo & Bonus — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-anggota />
@endsection

@section('topbar')
    <div>
        <h1 class="text-xl font-bold text-slate-900">Saldo & Bonus</h1>
        <p class="text-sm text-slate-600">Saldo available dan riwayat bonus</p>
    </div>
@endsection

@section('isi')
    @if (session('sukses'))
        <p role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('sukses') }}</p>
    @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-600">Saldo Available</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">Rp {{ number_format($saldoAvailable, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Siap ditarik</p>
            <a href="{{ route('tarik') }}" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                Tarik Saldo
            </a>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-600">Total Bonus</p>
            <p class="mt-2 text-3xl font-bold text-slate-900">Rp {{ number_format($totalBonus, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-500">Semua status</p>
        </div>
    </div>

    <section class="mt-8" aria-labelledby="judul-bonus">
        <h2 id="judul-bonus" class="text-lg font-bold text-slate-900">Riwayat Bonus</h2>
        @if ($riwayatBonus->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada bonus</p>
            </div>
        @else
            @include('member._tabel-bonus', ['bonus' => $riwayatBonus])
            <x-paginasi :paginator="$riwayatBonus" label="bonus" />
        @endif
    </section>

    <section class="mt-8" aria-labelledby="judul-tarik">
        <h2 id="judul-tarik" class="text-lg font-bold text-slate-900">Riwayat Penarikan</h2>
        @if ($riwayatTarik->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada penarikan</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <caption class="sr-only">Riwayat penarikan anggota</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Jumlah</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Bank</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($riwayatTarik as $w)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $w->requested_at->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-900">Rp {{ number_format($w->amount, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $w->bank_name }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ match ($w->status) {
                                        'requested' => 'bg-amber-100 text-amber-700',
                                        'approved' => 'bg-blue-100 text-blue-700',
                                        'paid' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    } }}">{{ ucfirst($w->status) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-paginasi :paginator="$riwayatTarik" label="penarikan" />
        @endif
    </section>
@endsection
