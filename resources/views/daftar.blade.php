@extends('layouts.dasar')

@section('judul', 'Daftar Anggota Baru — Smart Store Sahih')

@section('isi')
<div class="mx-auto max-w-xl">

    {{-- Header --}}
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-widest text-hijau-tua">Pendaftaran</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-tight">Daftar Anggota Baru</h1>
        <p class="mt-1 text-sm text-tinta/60">
            Siapkan PIN pendaftaran dari admin dan data diri Anda sebelum mengisi formulir ini.
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-kartu border border-bahaya-garis bg-bahaya-lembut p-4 text-sm text-bahaya" role="alert">
            <p class="font-semibold">Ada yang perlu diperbaiki:</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $pesan)
                    <li>{{ $pesan }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('daftar.store') }}"
          class="rounded-kartu border border-garis bg-kertas shadow-sm">
        @csrf

        {{-- Grup: Akun --}}
        <div class="border-b border-garis px-6 py-4">
            <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-tinta/50">Informasi Akun</p>
            <div class="space-y-4">

                <div>
                    <label for="username" class="mb-1 block text-sm font-medium">
                        Username <span class="text-bahaya">*</span>
                    </label>
                    <input id="username" name="username" type="text"
                           value="{{ old('username') }}" required maxlength="30"
                           autocomplete="username"
                           @error('username') aria-invalid="true" aria-describedby="username-err" @enderror
                           class="w-full rounded-lg border border-garis bg-krem px-3 py-2.5 text-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20 @error('username') border-bahaya @enderror">
                    @error('username')
                        <p id="username-err" class="mt-1 text-xs text-bahaya">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-xs text-tinta/50">Huruf, angka, titik, garis bawah, atau strip. Maks 30 karakter.</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1 block text-sm font-medium">
                        Password <span class="text-bahaya">*</span>
                    </label>
                    <input id="password" name="password" type="password"
                           required minlength="8" autocomplete="new-password"
                           @error('password') aria-invalid="true" aria-describedby="password-err" @enderror
                           class="w-full rounded-lg border border-garis bg-krem px-3 py-2.5 text-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20 @error('password') border-bahaya @enderror">
                    @error('password')
                        <p id="password-err" class="mt-1 text-xs text-bahaya">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-xs text-tinta/50">Minimal 8 karakter.</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium">
                        Ulangi Password <span class="text-bahaya">*</span>
                    </label>
                    <input id="password_confirmation" name="password_confirmation"
                           type="password" required minlength="8" autocomplete="new-password"
                           class="w-full rounded-lg border border-garis bg-krem px-3 py-2.5 text-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                </div>

            </div>
        </div>

        {{-- Grup: Data Diri --}}
        <div class="border-b border-garis px-6 py-4">
            <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-tinta/50">Data Diri</p>
            <div class="space-y-4">

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium">
                        Nama Lengkap <span class="text-bahaya">*</span>
                    </label>
                    <input id="name" name="name" type="text"
                           value="{{ old('name') }}" required maxlength="100"
                           autocomplete="name"
                           class="w-full rounded-lg border border-garis bg-krem px-3 py-2.5 text-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                </div>

                <div>
                    <label for="nik_ktp" class="mb-1 block text-sm font-medium">
                        NIK KTP <span class="text-bahaya">*</span>
                    </label>
                    <input id="nik_ktp" name="nik_ktp" type="text" inputmode="numeric"
                           value="{{ old('nik_ktp') }}" required
                           maxlength="16" minlength="16" pattern="\d{16}"
                           autocomplete="off"
                           @error('nik_ktp') aria-invalid="true" aria-describedby="nik-err" @enderror
                           placeholder="16 digit angka"
                           class="w-full rounded-lg border border-garis bg-krem px-3 py-2.5 text-sm font-mono tracking-wider outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20 @error('nik_ktp') border-bahaya @enderror"
                           oninput="this.value=this.value.replace(/\D/g,'').slice(0,16)">
                    @error('nik_ktp')
                        <p id="nik-err" class="mt-1 text-xs text-bahaya">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-xs text-tinta/50">Nomor Induk Kependudukan sesuai KTP, tepat 16 digit angka.</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="mb-1 block text-sm font-medium">
                        Nomor Telepon <span class="text-bahaya">*</span>
                    </label>
                    <input id="phone" name="phone" type="tel"
                           value="{{ old('phone') }}" required maxlength="30"
                           autocomplete="tel"
                           class="w-full rounded-lg border border-garis bg-krem px-3 py-2.5 text-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                </div>

                <div>
                    <label for="address" class="mb-1 block text-sm font-medium">Alamat</label>
                    <textarea id="address" name="address" rows="2" maxlength="255"
                              autocomplete="street-address"
                              class="w-full rounded-lg border border-garis bg-krem px-3 py-2.5 text-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">{{ old('address') }}</textarea>
                    <p class="mt-1 text-xs text-tinta/50">Opsional.</p>
                </div>

            </div>
        </div>

        {{-- Grup: Jaringan & PIN --}}
        <div class="px-6 py-4">
            <p class="mb-4 text-xs font-semibold uppercase tracking-widest text-tinta/50">Jaringan & Verifikasi</p>
            <div class="space-y-4">

                <div>
                    <label for="referral" class="mb-1 block text-sm font-medium">Kode Referral</label>
                    <input id="referral" name="referral" type="text"
                           value="{{ old('referral') }}" maxlength="30"
                           autocomplete="off"
                           @error('referral') aria-invalid="true" aria-describedby="referral-err" @enderror
                           class="w-full rounded-lg border border-garis bg-krem px-3 py-2.5 text-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20 @error('referral') border-bahaya @enderror">
                    @error('referral')
                        <p id="referral-err" class="mt-1 text-xs text-bahaya">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-xs text-tinta/50">Username sponsor (opsional). Kosongkan kalau tidak ada.</p>
                    @enderror
                </div>

                <div>
                    <label for="code" class="mb-1 block text-sm font-medium">
                        Kode PIN Pendaftaran <span class="text-bahaya">*</span>
                    </label>
                    <input id="code" name="code" type="text"
                           value="{{ old('code') }}" required maxlength="20"
                           autocomplete="off" spellcheck="false"
                           @error('code') aria-invalid="true" aria-describedby="code-err" @enderror
                           class="w-full rounded-lg border border-garis bg-krem px-3 py-2.5 font-mono text-sm uppercase tracking-wider outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20 @error('code') border-bahaya @enderror">
                    @error('code')
                        <p id="code-err" class="mt-1 text-xs text-bahaya">{{ $message }}</p>
                    @else
                        <p class="mt-1 text-xs text-tinta/50">Kode diperoleh dari admin. Tanpa kode, pendaftaran tidak bisa dilanjutkan.</p>
                    @enderror
                </div>

            </div>
        </div>

        {{-- Footer form --}}
        <div class="rounded-b-xl border-t border-garis bg-krem/50 px-6 py-4">
            <button type="submit"
                    class="w-full rounded-lg bg-amber px-4 py-3 font-semibold text-tinta shadow-sm transition hover:bg-amber/90 focus:outline-none focus:ring-2 focus:ring-amber/50 active:scale-[.99]">
                Daftar Sekarang
            </button>
            <p class="mt-3 text-center text-sm text-tinta/60">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-medium text-hijau-tua underline underline-offset-2 hover:text-hijau">Masuk</a>
            </p>
        </div>

    </form>
</div>
@endsection
