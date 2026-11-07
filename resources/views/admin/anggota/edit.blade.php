@extends('layouts.dasar')

@section('judul', 'Edit Anggota — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Edit Anggota</p>
        <p class="text-base font-bold text-slate-900">{{ $anggota->username }}</p>
    </div>
@endsection

@section('isi')
    @if (session('status'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.anggota.update', $anggota) }}" class="max-w-2xl space-y-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
        @csrf

        <label class="block">
            <span class="text-sm font-medium text-slate-700">Username</span>
            <input type="text" value="{{ $anggota->username }}" disabled
                   class="mt-1 w-full cursor-not-allowed rounded-lg border border-slate-300 bg-slate-100 px-3 py-2.5 text-slate-500">
        </label>

        <label class="block">
            <span class="text-sm font-medium text-slate-700">NIK KTP</span>
            <input type="text" value="{{ $anggota->nik_ktp ?? '—' }}" disabled
                   class="mt-1 w-full cursor-not-allowed rounded-lg border border-slate-300 bg-slate-100 px-3 py-2.5 text-slate-500">
        </label>

        <label class="block">
            <span class="text-sm font-medium text-slate-700">Nama</span>
            <input type="text" name="name" value="{{ old('name', $anggota->name) }}"
                   @error('name') aria-invalid="true" @enderror
                   class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
            @error('name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium text-slate-700">Telepon</span>
            <input type="text" name="phone" value="{{ old('phone', $anggota->phone) }}"
                   @error('phone') aria-invalid="true" @enderror
                   class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
            @error('phone') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium text-slate-700">Alamat</span>
            <textarea name="address" rows="3"
                      class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">{{ old('address', $anggota->address) }}</textarea>
            @error('address') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium text-slate-700">Status akun</span>
            <select name="status" class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
                <option value="active" @selected(old('status', $anggota->status) === 'active')>Aktif</option>
                <option value="suspended" @selected(old('status', $anggota->status) === 'suspended')>Dibekukan</option>
            </select>
            <span class="mt-1 block text-xs text-slate-500">Anggota yang dibekukan tidak bisa memakai PIN atau mengajukan hadiah.</span>
            @error('status') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Simpan</button>
            <a href="{{ route('admin.anggota.show', $anggota) }}" class="text-sm text-slate-600 underline decoration-slate-300 underline-offset-2 hover:text-slate-900">Kembali</a>
        </div>
    </form>

    <div class="mt-6 rounded-lg border border-red-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-slate-900">Reset Password</h2>
        <p class="mt-1 text-xs text-slate-500">Admin menetapkan sandi baru. Sandi tidak ditampilkan lagi setelah disimpan; anggota sebaiknya menggantinya sendiri setelah masuk.</p>

        <form method="POST" action="{{ route('admin.anggota.reset-password', $anggota) }}" class="mt-4 space-y-4">
            @csrf
            <label class="block">
                <span class="text-sm font-medium text-slate-700">Password baru</span>
                <input type="password" name="password" autocomplete="new-password" minlength="8"
                       @error('password') aria-invalid="true" @enderror
                       class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                @error('password') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium text-slate-700">Ulangi password baru</span>
                <input type="password" name="password_confirmation" autocomplete="new-password" minlength="8"
                       class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
            </label>

            <button type="submit" class="rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Reset Password</button>
        </form>
    </div>
@endsection
