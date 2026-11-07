@extends('layouts.dasar')

@section('judul', 'Profil — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-anggota />
@endsection

@section('topbar')
    <div>
        <h1 class="text-xl font-bold text-slate-900">Profil Saya</h1>
        <p class="text-sm text-slate-600">Edit informasi akun</p>
    </div>
@endsection

@section('isi')
    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('profil.update') }}" class="max-w-2xl space-y-6">
        @csrf

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Informasi Dasar</h2>
            <p class="mt-1 text-sm text-slate-600">Data diri yang bisa diubah.</p>

            <div class="mt-5 space-y-4">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Username</span>
                    <input type="text" value="{{ $member->username }}" disabled
                           class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-100 px-3 py-2.5 text-slate-500 cursor-not-allowed">
                    <span class="mt-1 block text-xs text-slate-500">Username tidak bisa diubah.</span>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">NIK KTP</span>
                    <input type="text" value="{{ $member->nik_ktp ?? '—' }}" disabled
                           class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-100 px-3 py-2.5 text-slate-500 cursor-not-allowed">
                    <span class="mt-1 block text-xs text-slate-500">NIK tidak bisa diubah. Hubungi admin jika ada kesalahan.</span>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Nama Lengkap</span>
                    <input type="text" name="name" value="{{ old('name', $member->name) }}"
                           @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                           class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
                    @error('name')
                        <span id="name-error" class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Nomor Telepon</span>
                    <input type="text" name="phone" value="{{ old('phone', $member->phone) }}"
                           @error('phone') aria-invalid="true" aria-describedby="phone-error" @enderror
                           class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
                    @error('phone')
                        <span id="phone-error" class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Alamat</span>
                    <textarea name="address" rows="3"
                              @error('address') aria-invalid="true" aria-describedby="address-error" @enderror
                              class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">{{ old('address', $member->address) }}</textarea>
                    @error('address')
                        <span id="address-error" class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </label>
            </div>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900">Ubah Password</h2>
            <p class="mt-1 text-sm text-slate-600">Kosongkan jika tidak ingin mengubah password.</p>

            <div class="mt-5 space-y-4">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Password Baru</span>
                    <input type="password" name="password"
                           @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                           class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
                    <span class="mt-1 block text-xs text-slate-500">Minimal 8 karakter.</span>
                    @error('password')
                        <span id="password-error" class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Konfirmasi Password</span>
                    <input type="password" name="password_confirmation"
                           class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                Simpan Perubahan
            </button>
            <a href="{{ route('beranda') }}" class="text-sm text-slate-600 underline decoration-slate-300 underline-offset-2 hover:text-slate-900">Batal</a>
        </div>
    </form>
@endsection
