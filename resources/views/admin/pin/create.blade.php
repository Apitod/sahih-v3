@extends('layouts.dasar')

@section('judul', 'Buat PIN — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Buat PIN</p>
    </div>
@endsection

@section('isi')
    <div>
        <h1 class="font-display text-2xl tracking-tight">Buat PIN</h1>
        <p class="mt-1 text-sm text-tinta/70">
            Harga PIN RO: Rp {{ number_format($hargaRo, 0, ',', '.') }} per pin.
            PIN registrasi/upgrade: Rp {{ number_format($hargaRegistrasi, 0, ',', '.') }} per pin.
            <a href="{{ route('admin.pengaturan') }}" class="underline decoration-garis underline-offset-2 hover:text-tinta">Ubah harga</a>.
        </p>
    </div>

    @if (session('status'))
        <p role="status"
           class="mt-4 rounded-kartu border border-hijau/40 bg-hijau/10 px-3 py-2 text-sm text-hijau-tua">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p role="alert"
           class="mt-4 rounded-kartu border border-bahaya-garis bg-bahaya-lembut px-3 py-2 text-sm text-bahaya">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('admin.pin.store') }}" class="mt-5 max-w-xl space-y-4 rounded-kartu border border-garis bg-kertas p-5 shadow-sm">
        @csrf

        <label class="block">
            <span class="text-sm font-medium">Jenis PIN</span>
            <select name="type" id="jenis"
                    class="mt-1 w-full rounded-lg border border-garis bg-krem px-3 py-2.5 outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20"
                    @error('type') aria-invalid="true" aria-describedby="type-error" @enderror>
                @foreach (['registrasi_upgrade' => 'Registrasi / naik peringkat', 'ro' => 'Repeat order (RO)'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(old('type') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            @error('type') <span id="type-error" class="mt-1 block text-xs text-bahaya">{{ $message }}</span> @enderror
        </label>

        {{-- PIN gratis: hanya admin yang boleh terbitkan (Owner 9 Nov). --}}
        <label class="flex cursor-pointer items-center gap-3 rounded-kartu border border-garis bg-krem/60 p-4">
            <input type="checkbox" name="is_free" value="1" @checked(old('is_free'))
                   class="h-5 w-5 rounded border-garis text-hijau focus:ring-hijau/20"
                   @error('is_free') aria-invalid="true" aria-describedby="is_free-error" @enderror>
            <div>
                <span class="text-sm font-semibold">PIN Gratis</span>
                <p class="text-xs text-tinta/60">0 poin, Rp 0, untuk pendaftaran anggota baru dengan peringkat target tertentu.</p>
            </div>
            @error('is_free') <span id="is_free-error" class="ml-auto text-xs text-bahaya">{{ $message }}</span> @enderror
        </label>

        {{-- Verifikasi username dua kali (permintaan Owner): PIN salah pemegang = salah harga. --}}
        <div class="rounded-kartu border border-garis bg-krem/60 p-4">
            <p class="text-sm text-tinta/70">
                Ketik username <strong class="text-tinta">dua kali</strong> untuk memastikan tidak salah.
                Kalau username belum punya akun, PIN ini dipakai untuk <strong class="text-tinta">mendaftarkan anggota baru</strong>.
                Kalau sudah punya akun, PIN ini <strong class="text-tinta">menaikkan peringkatnya</strong>.
            </p>

            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="text-sm font-medium">Username</span>
                    <input type="text" name="username" value="{{ old('username') }}"
                           autocomplete="off" spellcheck="false"
                           @error('username') aria-invalid="true" aria-describedby="username-error" @enderror
                           class="mt-1 w-full rounded-lg border border-garis bg-krem px-3 py-2.5 outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                    @error('username') <span id="username-error" class="mt-1 block text-xs text-bahaya">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Ketik ulang username</span>
                    <input type="text" name="username_confirmation" value="{{ old('username_confirmation') }}"
                           autocomplete="off" spellcheck="false"
                           @error('username_confirmation') aria-invalid="true" aria-describedby="username_confirmation-error" @enderror
                           class="mt-1 w-full rounded-lg border border-garis bg-krem px-3 py-2.5 outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                    @error('username_confirmation') <span id="username_confirmation-error" class="mt-1 block text-xs text-bahaya">{{ $message }}</span> @enderror
                </label>
            </div>

            <p class="mt-2 text-xs text-tinta/60">
                Kosongkan keduanya kalau PIN ini dijual bebas (tanpa pemegang), misalnya PIN RO.
                PIN Gratis wajib kosong — dipakai anggota baru saat pendaftaran.
            </p>
        </div>

        <label class="block">
            <span class="text-sm font-medium">
                Peringkat tujuan
                <span class="text-tinta/50">(kosongkan untuk pendaftaran berbayar; wajib diisi untuk PIN gratis)</span>
            </span>
            <select name="target_rank"
                    class="mt-1 w-full rounded-lg border border-garis bg-krem px-3 py-2.5 outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20"
                    @error('target_rank') aria-invalid="true" aria-describedby="target_rank-error" @enderror>
                <option value="">— tidak perlu / pendaftaran berbayar —</option>
                @foreach ($peringkat as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(old('target_rank') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-tinta/60">
                PIN gratis menentukan peringkat anggota baru. Manager tidak dijual lewat PIN — otomatis setelah Distributor + 20 poin PA.
            </span>
            @error('target_rank') <span id="target_rank-error" class="mt-1 block text-xs text-bahaya">{{ $message }}</span> @enderror
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
            <label class="block">
                <span class="text-sm font-medium">Jumlah pin</span>
                <input type="number" name="points" min="0" value="{{ old('points', 1) }}"
                       @error('points') aria-invalid="true" aria-describedby="points-error" @enderror
                       class="mt-1 w-full rounded-lg border border-garis bg-krem px-3 py-2.5 tabular-nums outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                <span class="mt-1 block text-xs text-tinta/60">
                    Untuk pendaftaran: 1 pin = Reseller, 3 pin = Agent, 6 pin = Distributor.
                </span>
                @error('points') <span id="points-error" class="mt-1 block text-xs text-bahaya">{{ $message }}</span> @enderror
            </label>
            <label class="block">
                <span class="text-sm font-medium">Harga (rupiah)</span>
                <input type="number" name="price" min="0" step="1000" value="{{ old('price', $hargaRegistrasi) }}"
                       @error('price') aria-invalid="true" aria-describedby="price-error" @enderror
                       class="mt-1 w-full rounded-lg border border-garis bg-krem px-3 py-2.5 tabular-nums outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                <span class="mt-1 block text-xs text-tinta/60">
                    RO: Rp {{ number_format($hargaRo, 0, ',', '.') }} &times; jumlah pin.
                    Registrasi/upgrade: Rp {{ number_format($hargaRegistrasi, 0, ',', '.') }} &times; jumlah pin.
                    Harga yang tidak sesuai hitungan akan ditolak.
                </span>
                @error('price') <span id="price-error" class="mt-1 block text-xs text-bahaya">{{ $message }}</span> @enderror
            </label>
        </div>

        <label class="block">
            <span class="text-sm font-medium">Berapa kode</span>
            <input type="number" name="quantity" min="1" max="50" value="{{ old('quantity', 1) }}"
                   @error('quantity') aria-invalid="true" aria-describedby="quantity-error" @enderror
                   class="mt-1 w-full rounded-lg border border-garis bg-krem px-3 py-2.5 tabular-nums outline-none transition focus:border-hijau focus:ring-2 focus:ring-hijau/20">
            @error('quantity') <span id="quantity-error" class="mt-1 block text-xs text-bahaya">{{ $message }}</span> @enderror
        </label>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                    class="rounded-kartu bg-amber px-4 py-2 text-sm font-medium text-tinta transition hover:bg-amber/90 focus:outline-none focus:ring-2 focus:ring-amber/50">Buat</button>
            <a href="{{ route('admin.pin.index') }}" class="text-sm text-tinta/70 underline decoration-garis underline-offset-2 hover:text-tinta">Batal</a>
        </div>
    </form>
@endsection
