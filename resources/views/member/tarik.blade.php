@extends('layouts.dasar')

@section('judul', 'Tarik Saldo — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-anggota />
@endsection

@section('topbar')
    <div>
        <h1 class="text-xl font-bold text-slate-900">Tarik Saldo</h1>
        <p class="text-sm text-slate-600">Ajukan penarikan bonus</p>
    </div>
@endsection

@section('isi')
    <div class="mx-auto max-w-2xl">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-600">Saldo Available</p>
            <p class="mt-2 text-3xl font-bold text-emerald-700">Rp {{ number_format($saldoAvailable, 0, ',', '.') }}</p>
        </div>

        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3">
                <ul class="list-disc pl-5 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('tarik.store') }}" class="mt-6 space-y-4">
            @csrf

            <div>
                <label for="amount" class="block text-sm font-medium text-slate-700">Jumlah Penarikan</label>
                <input type="number" name="amount" id="amount" required min="100000" max="{{ $saldoAvailable }}"
                       value="{{ old('amount') }}"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                <p class="mt-1 text-xs text-slate-500">Minimum penarikan Rp 100.000</p>
            </div>

            <div>
                <label for="bank_name" class="block text-sm font-medium text-slate-700">Nama Bank</label>
                <input type="text" name="bank_name" id="bank_name" required maxlength="50"
                       value="{{ old('bank_name') }}"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
            </div>

            <div>
                <label for="account_number" class="block text-sm font-medium text-slate-700">Nomor Rekening</label>
                <input type="text" name="account_number" id="account_number" required maxlength="40"
                       value="{{ old('account_number') }}"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
            </div>

            <div>
                <label for="account_holder" class="block text-sm font-medium text-slate-700">Nama Pemegang Rekening</label>
                <input type="text" name="account_holder" id="account_holder" required maxlength="100"
                       value="{{ old('account_holder') }}"
                       class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit"
                        class="flex-1 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700">
                    Ajukan Penarikan
                </button>
                <a href="{{ route('saldo') }}"
                   class="flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Batal
                </a>
            </div>
        </form>
    </div>
@endsection
