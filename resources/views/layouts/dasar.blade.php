<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Sahih Bodyfeed')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">

    {{-- Skip link accessibility --}}
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-600">
        Lompat ke konten
    </a>

    @hasSection('sidebar')
        {{-- Authenticated layout with sidebar --}}
        <div class="sidebar-layout">
            @yield('sidebar')

            <main id="main-content" class="flex flex-col">
                {{-- Top bar --}}
                <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
                    <div class="flex h-16 items-center justify-between px-6">
                        @yield('topbar')
                    </div>
                </header>

                {{-- Content area --}}
                <div class="flex-1 p-6">
                    <div class="mx-auto max-w-7xl">
                        @yield('isi')
                    </div>
                </div>

                {{-- Footer --}}
                <footer class="border-t border-slate-200 bg-white">
                    <div class="px-6 py-4 text-center text-xs text-slate-500">
                        © {{ date('Y') }} Sahih Bodyfeed · Network Marketing Platform
                    </div>
                </footer>
            </main>
        </div>

        {{-- Mobile bottom nav --}}
        @yield('mobile-nav')
    @else
        {{-- Guest layout - centered, no sidebar --}}
        <div class="flex min-h-screen flex-col">
            {{-- Header --}}
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
                <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                    {{-- Brand --}}
                    <a href="{{ url('/') }}"
                       class="flex items-center gap-3 transition-opacity hover:opacity-80">
                        <img src="{{ asset('logo.svg') }}" alt="" class="h-10 w-auto" aria-hidden="true">
                        <div class="flex flex-col">
                            <span class="text-lg font-bold leading-tight text-slate-900">Sahih Bodyfeed</span>
                            <span class="text-xs text-slate-600">Jaringan &amp; Poin</span>
                        </div>
                    </a>

                    @hasSection('nav')
                        <nav aria-label="Navigasi utama" class="flex items-center gap-2">
                            @yield('nav')
                        </nav>
                    @else
                        {{-- Guest navigation --}}
                        <nav aria-label="Navigasi utama" class="flex items-center gap-2">
                            <a href="{{ route('login') }}" @if (request()->routeIs('login')) aria-current="page" @endif
                               class="rounded-lg px-4 py-2 text-sm font-medium transition {{ request()->routeIs('login') ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                                Masuk
                            </a>
                            <a href="{{ route('daftar') }}" @if (request()->routeIs('daftar')) aria-current="page" @endif
                               class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                                Daftar
                            </a>
                        </nav>
                    @endif
                </div>
            </header>

            {{-- Content --}}
            <main id="main-content" class="mx-auto w-full max-w-7xl flex-1 px-6 py-8">
                @yield('isi')
            </main>

            {{-- Footer --}}
            <footer class="border-t border-slate-200 bg-white">
                <div class="mx-auto max-w-7xl px-6 py-4 text-center text-xs text-slate-500">
                    © {{ date('Y') }} Sahih Bodyfeed · Network Marketing Platform
                </div>
            </footer>
        </div>
    @endif

</body>
</html>
