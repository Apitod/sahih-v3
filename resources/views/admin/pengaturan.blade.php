@extends('layouts.dasar')

@section('judul', 'Pengaturan — Admin Sahih Bodyfeed')

@section('nav')
    <a href="{{ route('admin.beranda') }}" class="text-tinta/70 hover:underline">Ringkasan</a>
    <a href="{{ route('admin.pin.index') }}" class="text-tinta/70 hover:underline">PIN</a>
    <a href="{{ route('admin.pengaturan') }}" class="text-tinta/70 hover:underline">Pengaturan</a>
    <span class="text-tinta/70">{{ auth('admin')->user()->name }}</span>
@endsection

@section('isi')
    <x-judul-halaman
        judul="Pengaturan"
        keterangan="Angka di halaman ini dipakai langsung oleh perhitungan. Perubahan berlaku untuk transaksi berikutnya, bukan yang sudah tercatat." />

    @if (session('status'))
        <p class="mt-4 rounded-kartu border border-hijau/40 bg-hijau/10 px-4 py-3 text-sm text-hijau-tua">
            {{ session('status') }}
        </p>
    @endif

    <div class="mt-4 rounded-kartu border border-amber/50 bg-amber/10 px-4 py-3 text-sm text-tinta/80">
        <strong class="text-tinta">Nilai rupiah di halaman ini tidak boleh dibuka ke anggota.</strong>
        Halaman anggota hanya menampilkan angka poin dan nama hadiah.
    </div>

    <form method="POST" action="{{ route('admin.pengaturan.simpan') }}" class="mt-6 max-w-2xl space-y-5">
        @csrf

        @foreach ($label as $kunci => [$judul, $keterangan, $satuan])
            <div class="rounded-kartu border border-garis bg-kertas p-5">
                <label class="block">
                    <span class="text-sm font-medium text-tinta">{{ $judul }}</span>
                    <span class="mt-1 block text-xs text-tinta/60">{{ $keterangan }}</span>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        @if ($satuan === 'rupiah')
                            <span class="text-sm text-tinta/60">Rp</span>
                        @endif

                        <input type="number"
                               name="{{ $kunci }}"
                               id="{{ $kunci }}"
                               min="0"
                               step="{{ $satuan === 'rupiah' ? '1000' : '1' }}"
                               value="{{ old($kunci, $nilai[$kunci] ?? 0) }}"
                               class="w-48 rounded-kartu border border-garis px-3 py-2 text-right tabular-nums @error($kunci) border-red-400 @enderror">

                        @if ($satuan === 'poin')
                            <span class="text-sm text-tinta/60">poin</span>
                        @endif
                    </div>

                    @error($kunci)
                        <span class="mt-1 block text-xs text-red-700">{{ $message }}</span>
                    @enderror
                </label>
            </div>
        @endforeach

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit"
                    class="rounded-kartu bg-amber px-5 py-2.5 font-medium text-tinta hover:bg-amber/90">
                Simpan pengaturan
            </button>
            <span class="text-xs text-tinta/50">Setiap perubahan tercatat di jejak audit.</span>
        </div>
    </form>
@endsection
