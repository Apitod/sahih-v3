@extends('layouts.dasar')

@section('judul', 'Masuk Admin — Smart Store Sahih')

@section('isi')
    <div class="mx-auto max-w-sm">
        <h1 class="font-display text-2xl">Masuk Admin</h1>
        <p class="mt-1 text-sm text-tinta/70">Halaman ini untuk pengelola, bukan anggota.</p>

        <form method="POST" action="{{ route('admin.login.store') }}" class="mt-6 space-y-4 rounded-kartu border border-garis bg-kertas p-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="mt-1 w-full rounded-kartu border border-garis bg-krem px-3 py-2 text-sm outline-none focus:border-hijau">
                @error('email') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password" required
                       class="mt-1 w-full rounded-kartu border border-garis bg-krem px-3 py-2 text-sm outline-none focus:border-hijau">
                @error('password') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-kartu bg-amber px-4 py-2.5 text-sm font-semibold text-tinta hover:bg-amber/90">
                Masuk
            </button>
        </form>
    </div>
@endsection
