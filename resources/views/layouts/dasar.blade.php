<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Smart Store Sahih')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-krem font-sans text-tinta antialiased">
    <header class="border-b border-garis bg-kertas">
        <div class="mx-auto flex max-w-4xl items-center justify-between px-5 py-3">
            <a href="{{ url('/') }}" class="font-display text-lg tracking-tight">Smart Store Sahih</a>
            @hasSection('nav')
                <nav class="flex items-center gap-4 text-sm">@yield('nav')</nav>
            @endif
        </div>
    </header>

    <main class="mx-auto max-w-4xl px-5 py-8">
        @yield('isi')
    </main>

    <footer class="mx-auto max-w-4xl px-5 pb-10 text-xs text-tinta/50">
        Sahih Bodyfeed &middot; poin dan peringkat
    </footer>
</body>
</html>
