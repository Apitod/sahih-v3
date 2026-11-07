{{-- Admin Sidebar Navigation --}}
<aside class="sidebar hidden border-r border-slate-200 bg-white md:block">
    <div class="flex h-full flex-col">
        {{-- Brand --}}
        <div class="flex h-16 items-center gap-3 border-b border-slate-200 px-6">
            <img src="{{ asset('logo.svg') }}" alt="" class="h-10 w-auto" aria-hidden="true">
            <div class="flex flex-col">
                <span class="text-base font-bold leading-tight text-slate-900">Sahih Bodyfeed</span>
                <span class="text-xs text-red-600 font-semibold">Admin Panel</span>
            </div>
        </div>

        {{-- Navigation --}}
        <nav aria-label="Navigasi admin" class="flex-1 overflow-y-auto px-3 py-4">
            <ul class="space-y-1">
                @php
                    // ponytail: hitung per render; pindah ke view composer kalau nav terasa berat.
                    $menungguCair = config('sahih.pencairan_aktif')
                        ? \App\Models\Withdrawal::where('status', 'requested')->count()
                        : 0;

                    $navItems = [
                        ['route' => 'admin.beranda', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                        ['route' => 'admin.anggota.*', 'label' => 'Kelola Anggota', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['route' => 'admin.pin.*', 'label' => 'Kelola PIN', 'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
                        ['route' => 'admin.klaim', 'label' => 'Klaim Hadiah', 'icon' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7'],
                        ['route' => 'admin.transaksi.*', 'label' => 'Transaksi', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                        ['route' => 'admin.hadiah.*', 'label' => 'Hadiah', 'icon' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4'],
                        ...(config('sahih.pencairan_aktif') ? [['route' => 'admin.pencairan', 'label' => 'Pencairan', 'badge' => $menungguCair, 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z']] : []),
                        ['route' => 'admin.finansial', 'label' => 'Finansial', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                        ['route' => 'admin.panduan', 'label' => 'Panduan Admin', 'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ];

                    if (auth('admin')->user()?->isSuperadmin()) {
                        $navItems[] = ['route' => 'admin.pengaturan', 'label' => 'Pengaturan', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'];
                    }
                @endphp

                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['route']); @endphp
                    <li>
                        <a href="{{ route(str_replace('.*', '.index', $item['route']) === $item['route'] ? $item['route'] : str_replace('.*', '.index', $item['route'])) }}"
                           @if ($active) aria-current="page" @endif
                           class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' }}">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                            {{ $item['label'] }}
                            @if (! empty($item['badge']))
                                <span data-menunggu="{{ $item['badge'] }}" class="ml-auto rounded-full bg-red-600 px-2 py-0.5 text-xs font-bold text-white">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Admin info & logout --}}
        <div class="border-t border-slate-200 p-4">
            <div class="mb-3 flex items-center gap-3">
                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-red-500 to-red-700 text-sm font-bold text-white">
                    {{ strtoupper(mb_substr(auth('admin')->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-900">{{ auth('admin')->user()->name }}</p>
                    <p class="truncate text-xs text-slate-600">{{ ucfirst(auth('admin')->user()->role) }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.logout') }}">
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
<nav class="bottom-nav fixed bottom-0 left-0 right-0 z-40 flex justify-around border-t border-slate-200 bg-white px-2 py-2 md:hidden" aria-label="Navigasi mobile admin">
    @php
        $mobileNav = [
            ['route' => 'admin.beranda', 'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
            ['route' => 'admin.anggota.*', 'label' => 'Anggota', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
            ['route' => 'admin.pin.*', 'label' => 'PIN', 'icon' => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z'],
            ...(config('sahih.pencairan_aktif') ? [['route' => 'admin.pencairan', 'label' => 'Cair', 'badge' => $menungguCair, 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z']] : []),
            ['route' => 'admin.klaim', 'label' => 'Klaim', 'icon' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7'],
        ];
    @endphp

    @foreach ($mobileNav as $item)
        @php $active = request()->routeIs($item['route']); @endphp
        <a href="{{ route(str_replace('.*', '.index', $item['route']) === $item['route'] ? $item['route'] : str_replace('.*', '.index', $item['route'])) }}"
           @if ($active) aria-current="page" @endif
           class="relative flex flex-col items-center gap-1 rounded-lg px-3 py-2 text-xs font-medium transition {{ $active ? 'text-blue-700' : 'text-slate-600' }}">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
            </svg>
            {{ $item['label'] }}
            @if (! empty($item['badge']))
                <span data-menunggu="{{ $item['badge'] }}" class="absolute -top-0.5 right-1 rounded-full bg-red-600 px-1.5 text-[10px] font-bold text-white">{{ $item['badge'] }}</span>
            @endif
        </a>
    @endforeach

    <form method="POST" action="{{ route('admin.logout') }}" class="contents">
        @csrf
        <button type="submit" class="flex flex-col items-center gap-1 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Keluar
        </button>
    </form>
</nav>
