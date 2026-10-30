@extends('layouts.dasar')

@section('judul', 'Masuk — Smart Store Sahih')

@section('isi')
    <div class="mx-auto max-w-sm">
        <h1 class="font-display text-2xl">Masuk</h1>
        <p class="mt-1 text-sm text-tinta/70">Pakai username dan password yang diberikan admin.</p>

        <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4 rounded-kartu border border-garis bg-kertas p-5">
            @csrf

            <div>
                <label for="username" class="block text-sm font-medium">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                       class="mt-1 w-full rounded-kartu border border-garis bg-krem px-3 py-2 text-sm outline-none focus:border-hijau">
                @error('username') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password" required
                       class="mt-1 w-full rounded-kartu border border-garis bg-krem px-3 py-2 text-sm outline-none focus:border-hijau">
                @error('password') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="rounded border-garis">
                Ingat saya di perangkat ini
            </label>

            <button type="submit"
                    class="w-full rounded-kartu bg-amber px-4 py-2.5 text-sm font-semibold text-tinta hover:bg-amber/90">
                Masuk
            </button>
        </form>

        <p class="mt-4 text-xs text-tinta/60">
            Belum punya akun? Pendaftaran lewat admin — siapkan PIN registrasi.
        </p>
    </div>
@endsection
