@extends('layouts.dasar')

@section('judul', 'Buat PIN — Admin Sahih Bodyfeed')

@section('nav')
    <a href="{{ route('admin.beranda') }}" class="text-tinta/70 hover:underline">Ringkasan</a>
    <a href="{{ route('admin.pin.index') }}" class="text-tinta/70 hover:underline">PIN</a>
    <a href="{{ route('admin.pengaturan') }}" class="text-tinta/70 hover:underline">Pengaturan</a>
    <span class="text-tinta/70">{{ auth('admin')->user()->name }}</span>
@endsection

@section('isi')
    <h1 class="font-display text-2xl">Buat PIN</h1>
    <p class="mt-1 text-sm text-tinta/70">
        Harga PIN RO: Rp {{ number_format($hargaRo, 0, ',', '.') }} per pin.
        PIN registrasi/upgrade: Rp {{ number_format($hargaRegistrasi, 0, ',', '.') }} per pin.
        <a href="{{ route('admin.pengaturan') }}" class="underline">Ubah harga</a>.
    </p>

    <form method="POST" action="{{ route('admin.pin.store') }}" class="mt-6 max-w-xl space-y-4 rounded-kartu border border-garis bg-kertas p-5">
        @csrf

        <label class="block">
            <span class="text-sm text-tinta/70">Jenis PIN</span>
            <select name="type" id="jenis" class="mt-1 w-full rounded-kartu border border-garis px-3 py-2">
                @foreach (['registrasi_upgrade' => 'Registrasi / naik peringkat', 'ro' => 'Repeat order (RO)'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(old('type') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
        </label>

        {{-- Verifikasi username dua kali (permintaan Owner): PIN salah pemegang = salah harga. --}}
        <div class="rounded-kartu border border-garis bg-krem/60 p-4">
            <p class="text-sm text-tinta/70">
                Ketik username <strong class="text-tinta">dua kali</strong> untuk memastikan tidak salah.
                Kalau username belum punya akun, PIN ini dipakai untuk <strong class="text-tinta">mendaftarkan anggota baru</strong>.
                Kalau sudah punya akun, PIN ini <strong class="text-tinta">menaikkan peringkatnya</strong>.
            </p>

            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="text-sm text-tinta/70">Username</span>
                    <input type="text" name="username" value="{{ old('username') }}"
                           autocomplete="off" class="mt-1 w-full rounded-kartu border border-garis px-3 py-2">
                    @error('username') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm text-tinta/70">Ketik ulang username</span>
                    <input type="text" name="username_confirmation" value="{{ old('username_confirmation') }}"
                           autocomplete="off" class="mt-1 w-full rounded-kartu border border-garis px-3 py-2">
                    @error('username_confirmation') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>

            <p class="mt-2 text-xs text-tinta/60">
                Kosongkan keduanya kalau PIN ini dijual bebas (tanpa pemegang), misalnya PIN RO.
            </p>
        </div>

        <label class="block">
            <span class="text-sm text-tinta/70">
                Peringkat tujuan
                <span class="text-tinta/40">(kosongkan untuk mendaftarkan anggota baru)</span>
            </span>
            <select name="target_rank" class="mt-1 w-full rounded-kartu border border-garis px-3 py-2">
                <option value="">— tidak perlu / pendaftaran —</option>
                @foreach ($peringkat as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(old('target_rank') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-tinta/60">
                Manager tidak dijual lewat PIN — otomatis setelah Distributor + 20 poin PA.
            </span>
            @error('target_rank') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
                <span class="text-sm text-tinta/70">Jumlah pin</span>
                <input type="number" name="points" min="0" value="{{ old('points', 1) }}"
                       class="mt-1 w-full rounded-kartu border border-garis px-3 py-2 tabular-nums">
                <span class="mt-1 block text-xs text-tinta/60">
                    Untuk pendaftaran: 1 pin = Reseller, 3 pin = Agent, 6 pin = Distributor.
                </span>
                @error('points') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-sm text-tinta/70">Harga (rupiah)</span>
                <input type="number" name="price" min="0" step="1000" value="{{ old('price', $hargaRegistrasi) }}"
                       class="mt-1 w-full rounded-kartu border border-garis px-3 py-2 tabular-nums">
                <span class="mt-1 block text-xs text-tinta/60">
                    RO: Rp {{ number_format($hargaRo, 0, ',', '.') }} &times; jumlah pin.
                    Registrasi/upgrade: Rp {{ number_format($hargaRegistrasi, 0, ',', '.') }} &times; jumlah pin.
                    Harga yang tidak sesuai hitungan akan ditolak.
                </span>
                @error('price') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
            </label>
        </div>

        <label class="block">
            <span class="text-sm text-tinta/70">Berapa kode</span>
            <input type="number" name="quantity" min="1" max="50" value="{{ old('quantity', 1) }}"
                   class="mt-1 w-full rounded-kartu border border-garis px-3 py-2 tabular-nums">
            @error('quantity') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
        </label>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-kartu bg-amber px-4 py-2 text-sm font-medium text-tinta hover:bg-amber/90">Buat</button>
            <a href="{{ route('admin.pin.index') }}" class="text-sm text-tinta/70 hover:underline">Batal</a>
        </div>
    </form>
@endsection
