@extends('layouts.dasar')

@section('judul', 'Masuk — Smart Store Sahih')

@section('isi')
    <div class="grid items-center gap-8 lg:grid-cols-2">
        {{-- Panel kiri: hanya tampil di layar lebar, memberi konteks produk --}}
        <section aria-label="Tentang Smart Store Sahih"
                 class="hidden rounded-kartu border border-garis bg-kertas p-8 lg:block">
            <p class="text-xs font-semibold uppercase tracking-widest text-hijau-tua">Portal anggota</p>
            <h2 class="mt-3 font-display text-3xl leading-tight tracking-tight">
                Pantau poin, peringkat,<br>dan hadiah Anda.
            </h2>
            <p class="mt-3 max-w-sm text-sm text-tinta/70">
                Masuk untuk melihat total poin afiliasi, reward, dan leadership, menukar PIN
                upgrade atau repeat order, serta memeriksa hadiah yang sudah jadi hak Anda.
            </p>

            <dl class="mt-8 grid gap-3 sm:grid-cols-3">
                <div class="rounded-kartu border border-garis bg-krem p-3">
                    <dt class="text-xs text-tinta/60">Poin Afiliasi</dt>
                    <dd class="mt-1 text-sm font-medium">Pertumbuhan jaringan</dd>
                </div>
                <div class="rounded-kartu border border-garis bg-krem p-3">
                    <dt class="text-xs text-tinta/60">Poin Reward</dt>
                    <dd class="mt-1 text-sm font-medium">Belanja ulang</dd>
                </div>
                <div class="rounded-kartu border border-garis bg-krem p-3">
                    <dt class="text-xs text-tinta/60">Poin Leadership</dt>
                    <dd class="mt-1 text-sm font-medium">Kepemimpinan</dd>
                </div>
            </dl>
        </section>

        {{-- Panel kanan: formulir --}}
        <section aria-labelledby="judul-masuk" class="mx-auto w-full max-w-sm">
            <h1 id="judul-masuk" class="font-display text-2xl tracking-tight">Masuk</h1>
            <p class="mt-1 text-sm text-tinta/70">Pakai username dan password yang diberikan admin.</p>

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

            <form method="POST" action="{{ route('login.store') }}"
                  class="mt-6 space-y-4 rounded-kartu border border-garis bg-kertas p-6 shadow-sm">
                @csrf

                <div>
                    <label for="username" class="block text-sm font-medium">Username</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}"
                           required autofocus autocomplete="username"
                           @error('username') aria-invalid="true" aria-describedby="username-error" @enderror
                           class="mt-1 w-full rounded-kartu border border-garis bg-krem px-3 py-2 text-sm outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                    @error('username')
                        <p id="username-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>
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

                <label class="flex items-center gap-2 text-sm text-tinta/80">
                    <input type="checkbox" name="remember" value="1"
                           class="size-4 rounded border-garis text-hijau focus:ring-hijau/30">
                    Ingat saya di perangkat ini
                </label>

                <button type="submit"
                        class="w-full rounded-kartu bg-amber px-4 py-2.5 text-sm font-semibold text-tinta transition hover:bg-amber/90 focus:outline-none focus:ring-2 focus:ring-amber/50">
                    Masuk
                </button>
            </form>

            <div class="mt-4 space-y-1 text-xs text-tinta/60">
                <p>Belum punya akun? Pendaftaran lewat admin &mdash; siapkan PIN registrasi.</p>
                <p>
                    Pengelola?
                    <a href="{{ route('admin.login') }}" class="underline hover:text-tinta">Masuk sebagai admin</a>.
                </p>
            </div>
        </section>
    </div>
@endsection
