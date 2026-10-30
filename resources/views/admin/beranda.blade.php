@extends('layouts.dasar')

@section('judul', 'Admin — Smart Store Sahih')

@section('nav')
    <a href="{{ route('admin.pin.index') }}" class="text-tinta/70 hover:underline">PIN</a>
    <span class="text-tinta/70">{{ auth('admin')->user()->name }}</span>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="rounded-kartu border border-garis px-3 py-1.5 hover:bg-krem">Keluar</button>
    </form>
@endsection

@section('isi')
    <h1 class="font-display text-2xl">Ringkasan</h1>
    <p class="mt-1 text-sm text-tinta/70">Pantauan awal. <a href="{{ route('admin.pin.index') }}" class="underline">Kelola PIN</a> sudah bisa dipakai.</p>

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-kartu border border-garis bg-kertas p-4">
            <p class="text-sm text-tinta/60">Anggota</p>
            <p class="mt-1 font-display text-3xl tabular-nums">{{ number_format($jumlahAnggota, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-kartu border border-garis bg-kertas p-4">
            <p class="text-sm text-tinta/60">PIN belum dipakai</p>
            <p class="mt-1 font-display text-3xl tabular-nums">{{ number_format($jumlahPinBelumDipakai, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-kartu border border-garis bg-kertas p-4">
            <p class="text-sm text-tinta/60">Transaksi</p>
            <p class="mt-1 font-display text-3xl tabular-nums">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</p>
        </div>
    </div>

    <section class="mt-8">
        <h2 class="font-display text-lg">Transaksi terakhir</h2>
        @if ($transaksiTerakhir->isEmpty())
            <p class="mt-2 text-sm text-tinta/60">Belum ada transaksi.</p>
        @else
            <div class="mt-3 overflow-x-auto rounded-kartu border border-garis bg-kertas">
                <table class="w-full text-sm">
                    <thead class="border-b border-garis bg-krem/60 text-left text-xs uppercase tracking-wide text-tinta/60">
                        <tr>
                            <th class="px-4 py-2 font-medium">Tanggal</th>
                            <th class="px-4 py-2 font-medium">Anggota</th>
                            <th class="px-4 py-2 font-medium">Jenis</th>
                            <th class="px-4 py-2 font-medium text-right">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-garis">
                        @foreach ($transaksiTerakhir as $trx)
                            <tr>
                                <td class="px-4 py-2 text-tinta/70">{{ $trx->created_at?->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-2">{{ $trx->member?->username }}</td>
                                <td class="px-4 py-2">{{ ucfirst($trx->type) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
