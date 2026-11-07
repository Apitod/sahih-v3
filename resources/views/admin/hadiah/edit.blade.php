@extends('layouts.dasar')

@section('judul', 'Ubah Hadiah — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Ubah Hadiah</p>
    </div>
@endsection

@section('isi')
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Ubah Hadiah</h1>
        <p class="mt-1 text-sm text-slate-600">Ambang {{ $reward->threshold }} poin</p>
    </div>

    <form method="POST" action="{{ route('admin.hadiah.update', $reward) }}" class="mt-5 max-w-xl space-y-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        @csrf

        <label class="block">
            <span class="text-sm font-medium text-slate-700">Ambang poin</span>
            <input type="number" value="{{ $reward->threshold }}" readonly disabled
                   class="mt-1 w-full rounded-lg border border-slate-200 bg-slate-100 px-3 py-2.5 text-slate-600">
            <span class="mt-1 block text-xs text-slate-500">Ambang tidak bisa diubah — menggesernya akan mengacaukan hadiah yang sudah diklaim.</span>
        </label>

        <label class="block">
            <span class="text-sm font-medium text-slate-700">Nama hadiah</span>
            <input type="text" name="name" value="{{ old('name', $reward->name) }}" required maxlength="100"
                   @error('name') aria-invalid="true" @enderror
                   class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
            @error('name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium text-slate-700">Nilai internal (Rp)</span>
            <input type="number" name="value_internal" min="0" value="{{ old('value_internal', $reward->value_internal) }}" required
                   @error('value_internal') aria-invalid="true" @enderror
                   class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
            <span class="mt-1 block text-xs text-slate-500">Hanya untuk admin; tidak pernah tampil ke anggota.</span>
            @error('value_internal') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $reward->is_active)) class="rounded border-slate-300">
            Aktif
        </label>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Simpan</button>
            <a href="{{ route('admin.hadiah.index') }}" class="text-sm text-slate-600 underline decoration-slate-300 underline-offset-2 hover:text-slate-900">Kembali</a>
        </div>
    </form>
@endsection
