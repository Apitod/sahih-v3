@extends('layouts.dasar')

@section('judul', 'Kelola Anggota — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Kelola Anggota</p>
    </div>
@endsection

@section('isi')
    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') }}</div>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl tracking-tight">Anggota</h1>
            <p class="mt-1 text-sm text-slate-600">Lihat profil, genealogi, dan kelola status anggota.</p>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3 text-sm">
        <label class="flex flex-col gap-1">
            <span class="text-slate-600">Cari</span>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Username atau nama"
                   class="rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
        </label>
        <label class="flex flex-col gap-1">
            <span class="text-slate-600">Peringkat</span>
            <select name="rank" class="rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
                <option value="">semua</option>
                @foreach (['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('rank') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1">
            <span class="text-slate-600">Status</span>
            <select name="status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20">
                <option value="">semua</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="suspended" @selected(request('status') === 'suspended')>Dibekukan</option>
            </select>
        </label>
        <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm hover:bg-slate-50">Saring</button>
    </form>

    @if ($anggota->isEmpty())
        <div class="mt-6 max-w-md rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
            <p class="text-sm font-medium text-slate-700">Tidak ada anggota yang cocok</p>
            <p class="mt-1 text-sm text-slate-500">Coba ubah filter pencarian.</p>
        </div>
    @else
        <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-sm">
                <caption class="sr-only">Daftar anggota</caption>
                <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-semibold">Username</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Nama</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Peringkat</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Sponsor</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                        <th scope="col" class="px-4 py-3 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($anggota as $a)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-900">{{ $a->username }}</td>
                            <td class="px-4 py-3 text-slate-900">{{ $a->name }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ match ($a->rank) {
                                    'reseller' => 'bg-blue-100 text-blue-700',
                                    'agent' => 'bg-emerald-100 text-emerald-700',
                                    'distributor' => 'bg-amber-100 text-amber-700',
                                    'manager' => 'bg-purple-100 text-purple-700',
                                    default => 'bg-slate-100 text-slate-700',
                                } }}">{{ ucfirst($a->rank) }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $a->sponsor?->username ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ $a->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $a->status === 'active' ? 'Aktif' : 'Dibekukan' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.anggota.show', $a) }}" class="text-blue-600 hover:underline">Lihat</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-paginasi :paginator="$anggota" label="anggota" />
    @endif
@endsection
