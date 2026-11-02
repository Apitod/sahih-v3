@extends('layouts.dasar')

@section('judul', 'Admin — Smart Store Sahih')

@section('nav')
    <a href="{{ route('admin.beranda') }}" aria-current="page"
       class="rounded-kartu bg-krem px-3 py-1.5 font-medium text-tinta">Ringkasan</a>
    <a href="{{ route('admin.pin.index') }}" class="rounded-kartu px-3 py-1.5 text-tinta/70 hover:bg-krem hover:text-tinta">PIN</a>
    <span class="hidden text-tinta/60 sm:inline">{{ auth('admin')->user()->name }}</span>
    <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="rounded-kartu border border-garis px-3 py-1.5 hover:bg-krem">Keluar</button>
    </form>
@endsection

@section('isi')
    @php
        $labelPeringkat = [
            'reseller' => 'Reseller',
            'agent' => 'Agent',
            'distributor' => 'Distributor',
            'manager' => 'Manager',
        ];
        $warnaPeringkat = [
            'reseller' => 'bg-tinta/20',
            'agent' => 'bg-sky-400',
            'distributor' => 'bg-amber',
            'manager' => 'bg-hijau',
        ];
        $totalPeringkat = array_sum($status['peringkat']);
    @endphp

    {{-- Judul + status sesi admin --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl tracking-tight">Ringkasan</h1>
            <p class="mt-1 text-sm text-tinta/70">Pantauan kondisi keanggotaan, PIN, dan transaksi.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <x-badge tone="sukses">
                <span aria-hidden="true">●</span> Sesi admin aktif
            </x-badge>
            <x-badge tone="netral">{{ ucfirst($status['admin']->role) }}</x-badge>
        </div>
    </div>

    @if (session('status'))
        <p role="status"
           class="mt-4 rounded-kartu border border-hijau/40 bg-hijau/10 px-3 py-2 text-sm text-hijau-tua">
            {{ session('status') }}
        </p>
    @endif

    {{-- Status ringkas keanggotaan --}}
    <section class="mt-5 rounded-kartu border border-garis bg-kertas p-5" aria-labelledby="judul-status">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="judul-status" class="font-display text-lg">Status keanggotaan</h2>
            <p class="text-sm tabular-nums text-tinta/60">
                {{ $status['persen_aktif'] }}% aktif dari {{ number_format($status['total_anggota'], 0, ',', '.') }} anggota
            </p>
        </div>

        <div class="mt-3 h-2 overflow-hidden rounded-full bg-garis"
             role="progressbar"
             aria-valuenow="{{ $status['persen_aktif'] }}" aria-valuemin="0" aria-valuemax="100"
             aria-label="Persentase anggota aktif">
            <div class="h-full rounded-full bg-hijau transition-[width] duration-300"
                 style="width: {{ $status['persen_aktif'] }}%"></div>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <x-badge tone="sukses">{{ $status['anggota_aktif'] }} aktif</x-badge>
            <x-badge :tone="$status['anggota_beku'] > 0 ? 'bahaya' : 'netral'">{{ $status['anggota_beku'] }} dibekukan</x-badge>
            @if ($status['klaim_menunggu'] > 0)
                <x-badge tone="proses">{{ $status['klaim_menunggu'] }} klaim hadiah menunggu</x-badge>
            @endif
        </div>

        {{-- Sebaran peringkat --}}
        <div class="mt-5">
            <h3 class="text-sm font-medium text-tinta/70">Sebaran peringkat</h3>
            <ul class="mt-2 space-y-2">
                @foreach ($status['peringkat'] as $rank => $jumlah)
                    @php
                        $persen = $totalPeringkat > 0 ? (int) round($jumlah / $totalPeringkat * 100) : 0;
                    @endphp
                    <li class="flex items-center gap-3 text-sm">
                        <span class="w-24 shrink-0 text-tinta/70">{{ $labelPeringkat[$rank] }}</span>
                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-garis">
                            <span class="block h-full rounded-full {{ $warnaPeringkat[$rank] }}" style="width: {{ $persen }}%"></span>
                        </span>
                        <span class="w-16 shrink-0 text-right tabular-nums text-tinta/70">{{ $jumlah }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- Kartu angka --}}
    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-kartu-statistik label="Anggota" :nilai="number_format($jumlahAnggota, 0, ',', '.')"
                           :catatan="$status['anggota_aktif'].' aktif · '.$status['anggota_beku'].' beku'" />
        <x-kartu-statistik label="PIN belum dipakai" :nilai="number_format($jumlahPinBelumDipakai, 0, ',', '.')"
                           catatan="siap dijual" :tone="$jumlahPinBelumDipakai > 0 ? 'proses' : 'netral'" />
        <x-kartu-statistik label="Transaksi" :nilai="number_format($jumlahTransaksi, 0, ',', '.')"
                           catatan="Rp {{ number_format($status['nilai_transaksi'], 0, ',', '.') }} total nilai" />
        <x-kartu-statistik label="PIN dibatalkan" :nilai="number_format($status['pin']['void'], 0, ',', '.')"
                           :catatan="$status['pin']['used'].' sudah dipakai'"
                           :tone="$status['pin']['void'] > 0 ? 'bahaya' : 'netral'" />
    </div>

    {{-- Transaksi terakhir + paginasi --}}
    <section class="mt-8" aria-labelledby="judul-transaksi">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <h2 id="judul-transaksi" class="font-display text-lg">Transaksi terakhir</h2>
            <a href="{{ route('admin.pin.index') }}" class="text-sm text-tinta/70 underline hover:text-tinta">Kelola PIN</a>
        </div>

        @if ($transaksiTerakhir->isEmpty())
            <div class="mt-3">
                <x-kosong judul="Belum ada transaksi"
                          pesan="Transaksi tercatat saat anggota menukar PIN."
                          aksi="Buat PIN" :href="route('admin.pin.create')" />
            </div>
        @else
            <div class="mt-3 overflow-x-auto rounded-kartu border border-garis bg-kertas">
                <table class="w-full text-sm">
                    <caption class="sr-only">Daftar transaksi terakhir</caption>
                    <thead class="border-b border-garis bg-krem/60 text-left text-xs uppercase tracking-wide text-tinta/60">
                        <tr>
                            <th scope="col" class="px-4 py-2 font-medium">Tanggal</th>
                            <th scope="col" class="px-4 py-2 font-medium">Anggota</th>
                            <th scope="col" class="px-4 py-2 font-medium">Jenis</th>
                            <th scope="col" class="px-4 py-2 font-medium">Kode</th>
                            <th scope="col" class="px-4 py-2 text-right font-medium">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-garis">
                        @foreach ($transaksiTerakhir as $trx)
                            @php
                                $nadaJenis = match ($trx->type) {
                                    'join' => 'info',
                                    'upgrade' => 'proses',
                                    'ro' => 'sukses',
                                    default => 'netral',
                                };
                                $teksJenis = match ($trx->type) {
                                    'join' => 'pendaftaran',
                                    'upgrade' => 'upgrade',
                                    'ro' => 'repeat order',
                                    default => ucfirst($trx->type),
                                };
                            @endphp
                            <tr class="hover:bg-krem/40">
                                <td class="whitespace-nowrap px-4 py-2 text-tinta/70">{{ $trx->created_at?->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-2">{{ $trx->member?->username ?? '—' }}</td>
                                <td class="px-4 py-2"><x-badge :tone="$nadaJenis">{{ $teksJenis }}</x-badge></td>
                                <td class="px-4 py-2 font-mono text-xs text-tinta/70">{{ $trx->code }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-paginasi :paginator="$transaksiTerakhir" label="transaksi" />
        @endif
    </section>
@endsection
