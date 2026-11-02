@extends('layouts.dasar')

@section('judul', 'Tukar PIN — Smart Store Sahih')

@section('nav')
    <a href="{{ route('beranda') }}" class="rounded-kartu px-3 py-1.5 text-tinta/70 hover:bg-krem hover:text-tinta">Beranda</a>
    <a href="{{ route('tukar') }}" aria-current="page"
       class="rounded-kartu bg-krem px-3 py-1.5 font-medium text-tinta">Tukar PIN</a>
    <span class="hidden text-tinta/60 sm:inline">{{ auth('web')->user()->username }}</span>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="rounded-kartu border border-garis px-3 py-1.5 hover:bg-krem">Keluar</button>
    </form>
@endsection

@section('isi')
    @php
        $labelPeringkat = ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'];
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl tracking-tight">Tukar PIN</h1>
            <p class="mt-1 text-sm text-tinta/70">
                Masukkan kode PIN yang Anda terima dari admin. Satu PIN hanya bisa dipakai sekali.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-badge :tone="$status['aktif'] ? 'sukses' : 'bahaya'">
                <span aria-hidden="true">●</span> {{ $status['aktif'] ? 'Akun aktif' : 'Akun dibekukan' }}
            </x-badge>
            <x-badge tone="netral">{{ $labelPeringkat[$status['peringkat']] ?? ucfirst($status['peringkat']) }}</x-badge>
            <x-badge :tone="$status['jumlah_pin'] > 0 ? 'proses' : 'netral'">
                {{ $status['jumlah_pin'] }} PIN siap dipakai
            </x-badge>
        </div>
    </div>

    @if (session('status'))
        <p role="status"
           class="mt-4 rounded-kartu border border-hijau/40 bg-hijau/10 px-3 py-2 text-sm text-hijau-tua">
            {{ session('status') }}
        </p>
    @endif

    @if ($errors->any())
        <p role="alert"
           class="mt-4 rounded-kartu border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
            {{ $errors->first() }}
        </p>
    @endif

    {{-- Form tukar --}}
    <form method="POST" action="{{ route('tukar.store') }}"
          class="mt-5 max-w-md space-y-3 rounded-kartu border border-garis bg-kertas p-5 shadow-sm">
        @csrf
        <label for="code" class="block">
            <span class="text-sm font-medium">Kode PIN</span>
            <input id="code" type="text" name="code" value="{{ old('code') }}"
                   autocomplete="off" autofocus inputmode="text" spellcheck="false"
                   @error('code') aria-invalid="true" aria-describedby="code-error" @enderror
                   class="mt-1 w-full rounded-kartu border border-garis bg-krem px-3 py-2 font-mono text-base uppercase tracking-widest outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20"
                   placeholder="XXXXXXXXXXXX">
            @error('code')
                <span id="code-error" class="mt-1 block text-xs text-red-700">{{ $message }}</span>
            @enderror
        </label>
        <button type="submit"
                class="rounded-kartu bg-amber px-4 py-2 text-sm font-medium text-tinta transition hover:bg-amber/90 focus:outline-none focus:ring-2 focus:ring-amber/50">
            Pakai PIN
        </button>
    </form>

    {{-- PIN yang dititipkan --}}
    <section class="mt-8" aria-labelledby="judul-pin">
        <h2 id="judul-pin" class="font-display text-lg">PIN yang dititipkan ke akun Anda</h2>
        @if ($pinSaya->isEmpty())
            <div class="mt-3">
                <x-kosong judul="Belum ada PIN yang dititipkan"
                          pesan="Hubungi admin kalau PIN Anda belum masuk." />
            </div>
        @else
            <ul class="mt-3 divide-y divide-garis rounded-kartu border border-garis bg-kertas">
                @foreach ($pinSaya as $pin)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                        <span class="font-mono tracking-wider">{{ $pin->code }}</span>
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge :tone="$pin->type === 'upgrade' ? 'proses' : 'info'">
                                {{ match ($pin->type) {
                                    'upgrade' => 'upgrade ke '.($peringkat[$pin->target_rank] ?? '?'),
                                    'ro' => 'repeat order',
                                    'registration' => 'registrasi',
                                    default => $pin->type,
                                } }}
                            </x-badge>
                            <span class="text-tinta/70">{{ number_format($pin->points, 0, ',', '.') }} poin</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Riwayat transaksi + paginasi --}}
    <section class="mt-8" aria-labelledby="judul-riwayat-trx">
        <h2 id="judul-riwayat-trx" class="font-display text-lg">Riwayat transaksi Anda</h2>
        @if ($riwayat->isEmpty())
            <div class="mt-3">
                <x-kosong judul="Belum ada transaksi"
                          pesan="Riwayat muncul setelah Anda memakai PIN." />
            </div>
        @else
            <div class="mt-3 overflow-x-auto rounded-kartu border border-garis bg-kertas">
                <table class="w-full text-sm">
                    <caption class="sr-only">Riwayat transaksi anggota</caption>
                    <thead class="border-b border-garis bg-krem/60 text-left text-xs uppercase tracking-wide text-tinta/60">
                        <tr>
                            <th scope="col" class="px-4 py-2 font-medium">Tanggal</th>
                            <th scope="col" class="px-4 py-2 font-medium">Jenis</th>
                            <th scope="col" class="px-4 py-2 font-medium">PIN</th>
                            <th scope="col" class="px-4 py-2 text-right font-medium">Poin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-garis">
                        @foreach ($riwayat as $trx)
                            @php
                                $nadaTrx = match ($trx->type) {
                                    'join' => 'info',
                                    'upgrade' => 'proses',
                                    'ro' => 'sukses',
                                    default => 'netral',
                                };
                            @endphp
                            <tr class="hover:bg-krem/40">
                                <td class="whitespace-nowrap px-4 py-2 text-tinta/70">{{ $trx->posted_at?->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-2">
                                    <x-badge :tone="$nadaTrx">{{ match ($trx->type) {
                                        'join' => 'pendaftaran',
                                        'upgrade' => 'upgrade peringkat',
                                        'ro' => 'repeat order',
                                        default => $trx->type,
                                    } }}</x-badge>
                                </td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $trx->pin?->code ?? '—' }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">{{ number_format($trx->points, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-paginasi :paginator="$riwayat" label="transaksi" />
        @endif
    </section>
@endsection
