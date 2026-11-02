<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Smart Store Sahih')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-dvh flex-col bg-krem font-sans text-tinta antialiased">
    <a href="#konten-utama"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-kartu focus:bg-kertas focus:px-3 focus:py-2 focus:text-sm focus:shadow">
        Lompat ke konten
    </a>

    <header class="sticky top-0 z-20 border-b border-garis bg-kertas/95 backdrop-blur">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-x-4 gap-y-2 px-5 py-3">
            <a href="{{ url('/') }}" class="flex items-center gap-2 font-display text-lg tracking-tight">
                <span aria-hidden="true"
                      class="grid size-8 place-items-center rounded-full bg-hijau/15 text-sm font-semibold text-hijau-tua">S</span>
                Smart Store Sahih
            </a>

            @hasSection('nav')
                <nav aria-label="Navigasi utama" class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                    @yield('nav')
                </nav>
            @endif
        </div>
    </header>

    <main id="konten-utama" class="mx-auto w-full max-w-5xl flex-1 px-5 py-8">
        @yield('isi')
    </main>

    <footer class="mx-auto w-full max-w-5xl px-5 pb-10 text-xs text-tinta/50">
        Sahih Bodyfeed &middot; poin dan peringkat
    </footer>
</body>
</html>
