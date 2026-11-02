@extends('layouts.dasar')

@section('judul', 'Masuk Admin — Smart Store Sahih')

@section('isi')
    <div class="mx-auto w-full max-w-sm">
        <div class="mb-6 flex items-center gap-2">
            <span aria-hidden="true"
                  class="grid size-9 place-items-center rounded-full bg-tinta text-sm font-semibold text-krem">A</span>
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-tinta/50">Area pengelola</p>
                <p class="text-sm text-tinta/70">Bukan halaman anggota</p>
            </div>
        </div>

        <h1 class="font-display text-2xl tracking-tight">Masuk Admin</h1>
        <p class="mt-1 text-sm text-tinta/70">Gunakan email dan password admin Anda.</p>

        @if (session('status'))
            <p role="status"
               class="mt-4 rounded-kartu border border-hijau/40 bg-hijau/10 px-3 py-2 text-sm text-hijau-tua">
                {{ session('status') }}
            </p>
        @endif

        @if ($errors->any())
            <p role="alert"
               class="mt-4 rounded-kartu border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">
                {{ $errors->first() }}
            </p>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}"
              class="mt-6 space-y-4 rounded-kartu border border-garis bg-kertas p-6 shadow-sm">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       required autofocus autocomplete="username"
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                       class="mt-1 w-full rounded-kartu border border-garis bg-krem px-3 py-2 text-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                @error('email')
                    <p id="email-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password"
                       required autocomplete="current-password"
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                       class="mt-1 w-full rounded-kartu border border-garis bg-krem px-3 py-2 text-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                @error('password')
                    <p id="password-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full rounded-kartu bg-tinta px-4 py-2.5 text-sm font-semibold text-krem transition hover:bg-tinta/90 focus:outline-none focus:ring-2 focus:ring-tinta/40">
                Masuk sebagai admin
            </button>
        </form>

        <p class="mt-4 text-xs text-tinta/60">
            Anda anggota?
            <a href="{{ route('login') }}" class="underline hover:text-tinta">Masuk di halaman anggota</a>.
        </p>
    </div>
@endsection
