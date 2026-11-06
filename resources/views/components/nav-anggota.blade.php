{{-- Member Sidebar Navigation --}}
<aside class="sidebar hidden border-r border-slate-200 bg-white md:block">
    <div class="flex h-full flex-col">
        {{-- Brand --}}
        <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-6">
            <img src="{{ asset('logo.svg') }}" alt="" class="h-10 w-auto" aria-hidden="true">
            <div class="flex flex-col">
                <span class="text-base font-bold leading-tight text-slate-900">Sahih Bodyfeed</span>
                <span class="text-xs text-slate-600">Member Area</span>
            </div>
        </div>

        {{-- Navigation --}}
        <nav aria-label="Navigasi utama" class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-1">
                @php
                    $navItems = [
                        ['route' => 'beranda', 'label' => 'Beranda', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'tukar', 'label' => 'Tukar PIN', 'icon' => 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z'],
                    ];
                @endphp

                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <li>
                        <a href="{{ route($item['route']) }}"
                           @if ($active) aria-current="page" @endif
                           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-emerald-50 text-emerald-700' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- User info & logout --}}
        <div class="border-t border-slate-200 p-4">
            <div class="mb-3 flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-emerald-700 text-sm font-bold text-white">
                    {{ strtoupper(mb_substr(auth('web')->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ auth('web')->user()->name }}</p>
                    <p class="truncate text-xs text-slate-600">{{ auth('web')->user()->username }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Keluar
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Mobile bottom navigation --}}
<nav class="bottom-nav fixed bottom-0 left-0 right-0 z-40 flex justify-around border-t border-slate-200 bg-white px-2 py-2 md:hidden" aria-label="Navigasi mobile">
    @php
        $mobileNav = [
            ['route' => 'beranda', 'label' => 'Beranda', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['route' => 'tukar', 'label' => 'PIN', 'icon' => 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z'],
        ];
    @endphp

    @foreach ($mobileNav as $item)
        @php $active = request()->routeIs($item['route']); @endphp
        <a href="{{ route($item['route']) }}"
           @if ($active) aria-current="page" @endif
           class="flex flex-col items-center gap-1 rounded-lg px-3 py-2 text-xs font-medium transition {{ $active ? 'text-emerald-700' : 'text-slate-600' }}">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
            </svg>
            {{ $item['label'] }}
        </a>
    @endforeach

    <form method="POST" action="{{ route('logout') }}" class="contents">
        @csrf
        <button type="submit" class="flex flex-col items-center gap-1 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Keluar
        </button>
    </form>
</nav>
