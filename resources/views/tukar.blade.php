@extends('layouts.dasar')

@section('judul', 'Tukar PIN — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-anggota />
@endsection

@section('topbar')
    <div>
        <h1 class="text-xl font-bold text-slate-900">Tukar PIN</h1>
        <p class="text-sm text-slate-600">Repeat order dan naik peringkat</p>
    </div>
    <span class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-semibold {{ $status['aktif'] ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
        {{ $status['aktif'] ? 'Akun aktif' : 'Akun dibekukan' }}
    </span>
@endsection

@section('isi')
    @php
        $labelPeringkat = ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'];
    @endphp

    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Form tukar --}}
        <form method="POST" action="{{ route('tukar.store') }}"
              class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            @csrf
            <h2 class="text-lg font-bold text-slate-900">Masukkan kode PIN</h2>
            <p class="mt-1 text-sm text-slate-600">Satu PIN hanya bisa dipakai sekali dan tidak punya masa kedaluwarsa.</p>

            <label for="code" class="mt-5 block text-sm font-medium text-slate-700">Kode PIN</label>
            <input id="code" type="text" name="code" value="{{ old('code') }}"
                   autocomplete="off" autofocus spellcheck="false" placeholder="XXXXXXXXXXXX"
                   @error('code') aria-invalid="true" aria-describedby="code-error" @enderror
                   class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-3 font-mono text-lg uppercase tracking-widest outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
            @error('code')
                <p id="code-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror

            <button type="submit"
                    class="mt-5 inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                Pakai PIN
            </button>
        </form>

        {{-- Panduan --}}
        <aside class="rounded-lg border border-blue-200 bg-blue-50 p-6">
            <h2 class="text-sm font-bold uppercase tracking-wide text-blue-900">Jenis PIN</h2>
            <p class="mt-2 text-sm text-blue-900/90">Ada dua jenis PIN: PIN RO untuk repeat order, dan PIN registrasi/upgrade untuk naik peringkat.</p>
            <p class="mt-3 text-sm text-blue-900/90">Naik peringkat juga bisa otomatis dari total Poin Reward Anda.</p>
            <p class="mt-4 text-xs text-blue-900/70">{{ $status['jumlah_pin'] }} PIN siap dipakai di akun Anda.</p>
        </aside>
    </div>

    {{-- PIN yang dititipkan --}}
    <section class="mt-8" aria-labelledby="judul-pin">
        <h2 id="judul-pin" class="text-lg font-bold text-slate-900">PIN yang dititipkan ke akun Anda</h2>
        @if ($pinSaya->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada PIN yang dititipkan</p>
                <p class="mt-1 text-sm text-slate-500">Hubungi admin kalau PIN Anda belum masuk.</p>
            </div>
        @else
            <ul class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                @foreach ($pinSaya as $pin)
                    <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                        <span class="font-mono tracking-wider text-slate-900">{{ $pin->code }}</span>
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $pin->untukUpgrade() ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ match (true) {
                                    $pin->untukUpgrade() => 'naik peringkat ke '.($peringkat[$pin->target_rank] ?? '?'),
                                    $pin->isRo() => 'repeat order',
                                    default => 'pendaftaran',
                                } }}
                            </span>
                            <span class="text-slate-600">{{ number_format($pin->points, 0, ',', '.') }} poin</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Riwayat transaksi --}}
    <section class="mt-8" aria-labelledby="judul-riwayat-trx">
        <h2 id="judul-riwayat-trx" class="text-lg font-bold text-slate-900">Riwayat transaksi</h2>
        @if ($riwayat->isEmpty())
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
                        @foreach ($riwayat as $trx)
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
            <x-paginasi :paginator="$riwayat" label="transaksi" />
        @endif
    </section>
@endsection
