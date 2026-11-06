@extends('layouts.dasar')

@section('judul', 'Pengaturan — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Pengaturan</p>
    </div>
@endsection

@section('isi')
    <x-judul-halaman
        judul="Pengaturan"
        keterangan="Angka di halaman ini dipakai langsung oleh perhitungan. Perubahan berlaku untuk transaksi berikutnya, bukan yang sudah tercatat." />

    @if (session('status'))
        <p role="status"
           class="mt-4 rounded-kartu border border-hijau/40 bg-hijau/10 px-4 py-3 text-sm text-hijau-tua">
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
                               @error($kunci) aria-invalid="true" aria-describedby="{{ $kunci }}-error" @enderror
                               class="w-48 rounded-lg border border-garis bg-krem px-3 py-2.5 text-right tabular-nums outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20 @error($kunci) border-bahaya focus:border-bahaya focus:ring-red-400/20 @enderror">

                        @if ($satuan === 'poin')
                            <span class="text-sm text-tinta/60">poin</span>
                        @endif
                    </div>

                    @error($kunci)
                        <span id="{{ $kunci }}-error" class="mt-1 block text-xs text-bahaya">{{ $message }}</span>
                    @enderror
                </label>
            </div>
        @endforeach

        <div class="flex flex-wrap items-center gap-3">
            <button type="submit"
                    class="rounded-kartu bg-amber px-5 py-2.5 text-sm font-medium text-tinta transition hover:bg-amber/90 focus:outline-none focus:ring-2 focus:ring-amber/50">
                Simpan pengaturan
            </button>
            <span class="text-xs text-tinta/50">Setiap perubahan tercatat di jejak audit.</span>
        </div>
    </form>
@endsection
