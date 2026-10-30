@extends('layouts.dasar')

@section('judul', 'Buat PIN — Admin Smart Store Sahih')

@section('nav')
    <a href="{{ route('admin.beranda') }}" class="text-tinta/70 hover:underline">Ringkasan</a>
    <a href="{{ route('admin.pin.index') }}" class="text-tinta/70 hover:underline">PIN</a>
    <span class="text-tinta/70">{{ auth('admin')->user()->name }}</span>
@endsection

@section('isi')
    <h1 class="font-display text-2xl">Buat PIN</h1>
    <p class="mt-1 text-sm text-tinta/70">Jumlah poin dan harga registrasi/upgrade diisi manual. Khusus PIN RO mengikat Rp {{ number_format(config('sahih.ro_price_per_point', 500000), 0, ',', '.') }}/poin.</p>

    <form method="POST" action="{{ route('admin.pin.store') }}" class="mt-6 max-w-xl space-y-4 rounded-kartu border border-garis bg-kertas p-5">
        @csrf

        <label class="block">
            <span class="text-sm text-tinta/70">Jenis PIN</span>
            <select name="type" id="jenis" class="mt-1 w-full rounded-kartu border border-garis px-3 py-2">
                @foreach (['registration' => 'Registrasi (anggota baru)', 'upgrade' => 'Upgrade peringkat', 'ro' => 'Repeat order'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(old('type') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm text-tinta/70">Peringkat tujuan <span class="text-tinta/40">(wajib untuk PIN upgrade)</span></span>
            <select name="target_rank" class="mt-1 w-full rounded-kartu border border-garis px-3 py-2">
                <option value="">— tidak perlu —</option>
                @foreach ($peringkat as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(old('target_rank') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            @error('target_rank') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
                <span class="text-sm text-tinta/70">Jumlah poin</span>
                <input type="number" name="points" min="1" value="{{ old('points', 1) }}" class="mt-1 w-full rounded-kartu border border-garis px-3 py-2 tabular-nums">
                @error('points') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-sm text-tinta/70">Harga (rupiah)</span>
                <input type="number" name="price" min="0" step="1000" value="{{ old('price', 600000) }}" class="mt-1 w-full rounded-kartu border border-garis px-3 py-2 tabular-nums">
                <span class="mt-1 block text-xs text-tinta/60">Khusus PIN RO: wajib Rp {{ number_format(config('sahih.ro_price_per_point', 500000), 0, ',', '.') }} &times; jumlah poin.</span>
                @error('price') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
                <span class="text-sm text-tinta/70">Berapa kode</span>
                <input type="number" name="quantity" min="1" max="50" value="{{ old('quantity', 1) }}" class="mt-1 w-full rounded-kartu border border-garis px-3 py-2 tabular-nums">
                @error('quantity') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-sm text-tinta/70">Username pemegang <span class="text-tinta/40">(boleh kosong)</span></span>
                <input type="text" name="username" value="{{ old('username') }}" class="mt-1 w-full rounded-kartu border border-garis px-3 py-2">
                @error('username') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="rounded-kartu bg-amber px-4 py-2 text-sm font-medium text-tinta hover:bg-amber/90">Buat</button>
            <a href="{{ route('admin.pin.index') }}" class="text-sm text-tinta/70 hover:underline">Batal</a>
        </div>
    </form>
@endsection
