@extends('layouts.dasar')

@section('judul', 'Pohon Genealogi — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-anggota />
@endsection

@section('topbar')
    <div>
        <h1 class="text-xl font-bold text-slate-900">Pohon Genealogi</h1>
        <p class="text-sm text-slate-600">Jaringan Anda sampai 20 tingkat</p>
    </div>
@endsection

@section('isi')
    @if ($pohon->isNotEmpty())
        <div class="mb-3 flex flex-wrap items-center gap-2">
            <button type="button" id="zoom-in" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">+ Perbesar</button>
            <button type="button" id="zoom-out" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">− Perkecil</button>
            <button type="button" id="zoom-reset" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Reset</button>
            <span class="text-xs text-slate-500">Geser: klik + drag atau pinch</span>
        </div>

        <div id="tree-viewport" class="relative overflow-hidden rounded-lg border border-slate-200 bg-white" style="height: 70vh; touch-action: none;">
            <div id="tree-canvas" class="network-tree p-6" style="transform-origin: 0 0; will-change: transform;">
                <ul class="tree-root">
                    <li class="tree-node">
                        <div class="tree-card tree-card-root">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-emerald-700 text-sm font-bold text-white">
                                {{ strtoupper(mb_substr($member->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-900">{{ $member->name }}</p>
                                <p class="truncate text-xs text-slate-600">{{ $member->username }}</p>
                            </div>
                            <span class="rank-badge rank-{{ $member->rank }}">
                                {{ ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'][$member->rank] ?? ucfirst($member->rank) }}
                            </span>
                        </div>

                        @if ($pohon->isNotEmpty())
                            <ul class="tree-branch">
                                @foreach ($pohon as $simpul)
                                    <x-pohon-node :simpul="$simpul" :labelPeringkat="['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager']" />
                                @endforeach
                            </ul>
                        @endif
                    </li>
                </ul>
            </div>
        </div>

        <script>
        (function() {
            const viewport = document.getElementById('tree-viewport');
            const canvas = document.getElementById('tree-canvas');
            const zoomIn = document.getElementById('zoom-in');
            const zoomOut = document.getElementById('zoom-out');
            const zoomReset = document.getElementById('zoom-reset');

            let scale = 1;
            let translateX = 0;
            let translateY = 0;
            let isDragging = false;
            let startX = 0;
            let startY = 0;
            let lastTouchDist = 0;

            function updateTransform() {
                canvas.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
            }

            function zoom(delta, centerX = viewport.offsetWidth / 2, centerY = viewport.offsetHeight / 2) {
                const oldScale = scale;
                scale = Math.max(0.3, Math.min(3, scale + delta));
                const scaleChange = scale / oldScale;
                translateX = centerX - (centerX - translateX) * scaleChange;
                translateY = centerY - (centerY - translateY) * scaleChange;
                updateTransform();
            }

            zoomIn.addEventListener('click', () => zoom(0.2));
            zoomOut.addEventListener('click', () => zoom(-0.2));
            zoomReset.addEventListener('click', () => {
                scale = 1;
                translateX = 0;
                translateY = 0;
                updateTransform();
            });

            viewport.addEventListener('wheel', (e) => {
                e.preventDefault();
                const delta = e.deltaY > 0 ? -0.1 : 0.1;
                zoom(delta, e.offsetX, e.offsetY);
            }, { passive: false });

            viewport.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.clientX - translateX;
                startY = e.clientY - translateY;
                viewport.style.cursor = 'grabbing';
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                translateX = e.clientX - startX;
                translateY = e.clientY - startY;
                updateTransform();
            });

            document.addEventListener('mouseup', () => {
                isDragging = false;
                viewport.style.cursor = 'grab';
            });

            viewport.style.cursor = 'grab';

            viewport.addEventListener('touchstart', (e) => {
                if (e.touches.length === 1) {
                    isDragging = true;
                    startX = e.touches[0].clientX - translateX;
                    startY = e.touches[0].clientY - translateY;
                } else if (e.touches.length === 2) {
                    isDragging = false;
                    const dx = e.touches[0].clientX - e.touches[1].clientX;
                    const dy = e.touches[0].clientY - e.touches[1].clientY;
                    lastTouchDist = Math.hypot(dx, dy);
                }
            });

            viewport.addEventListener('touchmove', (e) => {
                e.preventDefault();
                if (e.touches.length === 1 && isDragging) {
                    translateX = e.touches[0].clientX - startX;
                    translateY = e.touches[0].clientY - startY;
                    updateTransform();
                } else if (e.touches.length === 2) {
                    const dx = e.touches[0].clientX - e.touches[1].clientX;
                    const dy = e.touches[0].clientY - e.touches[1].clientY;
                    const dist = Math.hypot(dx, dy);
                    if (lastTouchDist > 0) {
                        const centerX = (e.touches[0].clientX + e.touches[1].clientX) / 2 - viewport.getBoundingClientRect().left;
                        const centerY = (e.touches[0].clientY + e.touches[1].clientY) / 2 - viewport.getBoundingClientRect().top;
                        zoom((dist - lastTouchDist) * 0.01, centerX, centerY);
                    }
                    lastTouchDist = dist;
                }
            }, { passive: false });

            viewport.addEventListener('touchend', () => {
                isDragging = false;
                lastTouchDist = 0;
            });
        })();
        </script>
    @else
        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
            <p class="text-sm font-medium text-slate-700">Belum ada anggota di jaringan Anda</p>
            <p class="mt-1 text-sm text-slate-500">Ajak orang bergabung menggunakan link referral Anda.</p>
        </div>
    @endif
@endsection
