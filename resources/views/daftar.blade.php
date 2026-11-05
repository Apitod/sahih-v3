@extends('layouts.dasar')

@section('judul', 'Daftar Anggota Baru')

@section('isi')
    <div class="mx-auto max-w-lg">
        <h1 class="font-display text-2xl font-bold tracking-tight">Daftar Anggota Baru</h1>
        <p class="mt-1 text-sm text-tinta/60">
            Masukkan kode PIN pendaftaran dari admin. Kode referral (username sponsor) bersifat opsional.
        </p>

        @if ($errors->any())
            <div class="mt-4 rounded-kartu border border-merah/30 bg-merah/5 p-4 text-sm text-merah" role="alert">
                <p class="font-semibold">Ada yang perlu diperbaiki:</p>
                <ul class="mt-1 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $pesan)
                        <li>{{ $pesan }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('daftar.store') }}" class="mt-6 space-y-4 rounded-kartu border border-garis bg-kertas p-6">
            @csrf

            <div>
                <label for="username" class="mb-1 block text-sm font-medium">Username</label>
                <input id="username" name="username" value="{{ old('username') }}" required maxlength="30"
                       class="w-full rounded-kartu border border-garis px-3 py-2 @error('username') border-merah @enderror">
                <p class="mt-1 text-xs text-tinta/50">Dipakai untuk masuk. Huruf, angka, titik, garis bawah, atau strip.</p>
            </div>

            <div>
                <label for="name" class="mb-1 block text-sm font-medium">Nama Lengkap</label>
                <input id="name" name="name" value="{{ old('name') }}" required maxlength="100"
                       class="w-full rounded-kartu border border-garis px-3 py-2">
            </div>

            <div>
                <label for="phone" class="mb-1 block text-sm font-medium">Nomor Telepon</label>
                <input id="phone" name="phone" value="{{ old('phone') }}" required maxlength="30"
                       class="w-full rounded-kartu border border-garis px-3 py-2">
            </div>

            <div>
                <label for="address" class="mb-1 block text-sm font-medium">Alamat</label>
                <textarea id="address" name="address" rows="2" maxlength="255"
                          class="w-full rounded-kartu border border-garis px-3 py-2">{{ old('address') }}</textarea>
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                <input id="password" type="password" name="password" required minlength="8"
                       class="w-full rounded-kartu border border-garis px-3 py-2 @error('password') border-merah @enderror">
            </div>

            <div>
                <label for="password_confirmation" class="mb-1 block text-sm font-medium">Ulangi Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8"
                       class="w-full rounded-kartu border border-garis px-3 py-2">
            </div>

            <div>
                <label for="referral" class="mb-1 block text-sm font-medium">Kode Referral (opsional)</label>
                <input id="referral" name="referral" value="{{ old('referral') }}" maxlength="30"
                       class="w-full rounded-kartu border border-garis px-3 py-2">
                <p class="mt-1 text-xs text-tinta/50">Username sponsor. Kosongkan kalau tidak ada yang mengajak.</p>
            </div>

            <div>
                <label for="code" class="mb-1 block text-sm font-medium">Kode PIN Pendaftaran</label>
                <input id="code" name="code" value="{{ old('code') }}" required maxlength="20"
                       class="w-full rounded-kartu border border-garis px-3 py-2 @error('code') border-merah @enderror">
            </div>

            <button type="submit"
                    class="w-full rounded-kartu bg-amber px-4 py-3 font-semibold text-tinta transition hover:bg-amber/90">
                Daftar
            </button>

            <p class="text-center text-sm text-tinta/60">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-medium text-hijau-tua underline">Masuk</a>
            </p>
        </form>
    </div>
@endsection
