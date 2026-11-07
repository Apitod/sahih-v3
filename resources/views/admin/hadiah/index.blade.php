@extends('layouts.dasar')

@section('judul', 'Hadiah Milestone — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Hadiah Milestone</p>
    </div>
@endsection

@section('isi')
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Hadiah Milestone</h1>
        <p class="mt-1 text-sm text-slate-600">Ambang poin tetap; yang bisa diubah hanya nama, nilai internal, dan status aktif.</p>
    </div>

    @if (session('status'))
        <p role="status" class="mt-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <caption class="sr-only">Daftar hadiah milestone</caption>
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3 font-semibold">Ambang</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Nama</th>
                    <th scope="col" class="px-4 py-3 text-right font-semibold">Nilai Internal</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($hadiah as $h)
                    <tr class="transition hover:bg-slate-50">
                        <td class="px-4 py-3 tabular-nums font-semibold">{{ $h->threshold }}</td>
                        <td class="px-4 py-3">{{ $h->name }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">Rp {{ number_format((int) $h->value_internal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ $h->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $h->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.hadiah.edit', $h) }}" class="text-blue-700 underline decoration-blue-200 underline-offset-2 hover:text-blue-900">Ubah</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada hadiah.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-base font-bold text-slate-900">Tambah hadiah</h2>
        <form method="POST" action="{{ route('admin.hadiah.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @csrf
            <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ambang poin</span>
                <input type="number" name="threshold" min="1" value="{{ old('threshold') }}" required
                       class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                @error('threshold') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block lg:col-span-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nama hadiah</span>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                       class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                @error('name') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nilai internal</span>
                <input type="number" name="value_internal" min="0" value="{{ old('value_internal') }}" required
                       class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                @error('value_internal') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
            </label>
            <div class="flex items-end gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-300">
                    Aktif
                </label>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Tambah</button>
            </div>
        </form>
    </div>
@endsection
