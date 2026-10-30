@extends('layouts.dasar')

@section('judul', 'Tukar PIN — Smart Store Sahih')

@section('nav')
    <a href="{{ route('beranda') }}" class="text-tinta/70 hover:underline">Beranda</a>
    <span class="text-tinta/70">{{ auth('web')->user()->username }}</span>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="rounded-kartu border border-garis px-3 py-1.5 hover:bg-krem">Keluar</button>
    </form>
@endsection

@section('isi')
    <h1 class="font-display text-2xl">Tukar PIN</h1>
    <p class="mt-1 text-sm text-tinta/70">Masukkan kode PIN yang Anda terima dari admin. Satu PIN hanya bisa dipakai sekali.</p>

    <form method="POST" action="{{ route('tukar.store') }}" class="mt-5 max-w-md space-y-3 rounded-kartu border border-garis bg-kertas p-5">
        @csrf
        <label class="block">
            <span class="text-sm text-tinta/70">Kode PIN</span>
            <input type="text" name="code" value="{{ old('code') }}" autocomplete="off" autofocus
                   class="mt-1 w-full rounded-kartu border border-garis px-3 py-2 font-mono text-base uppercase tracking-widest"
                   placeholder="XXXXXXXXXXXX">
            @error('code') <span class="mt-1 block text-xs text-red-700">{{ $message }}</span> @enderror
        </label>
        <button type="submit" class="rounded-kartu bg-amber px-4 py-2 text-sm font-medium text-tinta hover:bg-amber/90">Pakai PIN</button>
    </form>

    <section class="mt-8">
        <h2 class="font-display text-lg">PIN yang dititipkan ke akun Anda</h2>
        @if ($pinSaya->isEmpty())
            <p class="mt-2 text-sm text-tinta/60">Belum ada PIN yang dititipkan. Hubungi admin kalau PIN Anda belum masuk.</p>
        @else
            <ul class="mt-3 divide-y divide-garis rounded-kartu border border-garis bg-kertas">
                @foreach ($pinSaya as $pin)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                        <span class="font-mono tracking-wider">{{ $pin->code }}</span>
                        <span class="text-tinta/70">
                            {{ match ($pin->type) {
                                'upgrade' => 'upgrade ke '.($peringkat[$pin->target_rank] ?? '?'),
                                'ro' => 'repeat order',
                                'registration' => 'registrasi',
                                default => $pin->type,
                            } }}
                            &middot; {{ number_format($pin->points, 0, ',', '.') }} poin
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="mt-8">
        <h2 class="font-display text-lg">Riwayat transaksi Anda</h2>
        @if ($riwayat->isEmpty())
            <p class="mt-2 text-sm text-tinta/60">Belum ada transaksi.</p>
        @else
            <div class="mt-3 overflow-x-auto rounded-kartu border border-garis bg-kertas">
                <table class="w-full text-sm">
                    <thead class="border-b border-garis bg-krem/60 text-left text-xs uppercase tracking-wide text-tinta/60">
                        <tr>
                            <th class="px-4 py-2 font-medium">Tanggal</th>
                            <th class="px-4 py-2 font-medium">Jenis</th>
                            <th class="px-4 py-2 font-medium">PIN</th>
                            <th class="px-4 py-2 font-medium text-right">Poin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-garis">
                        @foreach ($riwayat as $trx)
                            <tr>
                                <td class="px-4 py-2 text-tinta/70">{{ $trx->posted_at?->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-2">{{ match ($trx->type) {
                                    'join' => 'pendaftaran',
                                    'upgrade' => 'upgrade peringkat',
                                    'ro' => 'repeat order',
                                    default => $trx->type,
                                } }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $trx->pin?->code ?? '—' }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ number_format($trx->points, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
