@extends('layouts.dasar')

@section('judul', 'Beranda — Smart Store Sahih')

@section('nav')
    <a href="{{ route('beranda') }}" aria-current="page"
       class="rounded-kartu bg-krem px-3 py-1.5 font-medium text-tinta">Beranda</a>
    <a href="{{ route('tukar') }}" class="rounded-kartu px-3 py-1.5 text-tinta/70 hover:bg-krem hover:text-tinta">Tukar PIN</a>
    <span class="hidden text-tinta/60 sm:inline">{{ $member->username }}</span>
    <form method="POST" action="{{ route('logout') }}">
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
        $labelPoin = ['pa' => 'Poin Afiliasi', 'pr' => 'Poin Reward', 'pl' => 'Poin Leadership'];
        $warnaPeringkat = [
            'reseller' => 'netral',
            'agent' => 'info',
            'distributor' => 'proses',
            'manager' => 'sukses',
        ];
    @endphp

    @if (session('status'))
        <p role="status"
           class="mb-5 rounded-kartu border border-hijau/40 bg-hijau/10 px-3 py-2 text-sm text-hijau-tua">
            {{ session('status') }}
            @if (session('status_trx'))
                <span class="text-hijau-tua/70">&middot; {{ session('status_trx') }}</span>
            @endif
        </p>
    @endif

    {{-- Identitas + status akun --}}
    <section class="rounded-kartu border border-garis bg-kertas p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h1 class="font-display text-2xl tracking-tight">Halo, {{ $member->name }}</h1>
                <p class="mt-1 text-sm text-tinta/70">
                    {{ $sponsor ? 'Di bawah sponsor '.$sponsor->username : 'Terdaftar tanpa sponsor' }}
                    &middot; bergabung {{ $member->joined_at?->translatedFormat('j F Y') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <x-badge :tone="$status['aktif'] ? 'sukses' : 'bahaya'">
                    <span aria-hidden="true">{{ $status['aktif'] ? '●' : '●' }}</span>
                    {{ $status['aktif'] ? 'Akun aktif' : 'Akun dibekukan' }}
                </x-badge>
                <x-badge :tone="$warnaPeringkat[$member->rank] ?? 'netral'">
                    {{ $labelPeringkat[$member->rank] ?? ucfirst($member->rank) }}
                </x-badge>
            </div>
        </div>

        {{-- Progres ke peringkat berikutnya --}}
        @if ($status['progres'])
            @php $p = $status['progres']; @endphp
            <div class="mt-5 rounded-kartu border border-garis bg-krem/60 p-4">
                <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                    <p class="font-medium">{{ $p['label'] }}</p>
                    <p class="tabular-nums text-tinta/70">
                        {{ number_format($p['sekarang'], 0, ',', '.') }} / {{ number_format($p['target'], 0, ',', '.') }}
                        {{ $p['satuan'] }}
                    </p>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-garis"
                     role="progressbar"
                     aria-valuenow="{{ $p['persen'] }}" aria-valuemin="0" aria-valuemax="100"
                     aria-label="{{ $p['label'] }}">
                    <div class="h-full rounded-full bg-hijau transition-[width] duration-300" style="width: {{ $p['persen'] }}%"></div>
                </div>
                <p class="mt-2 text-xs text-tinta/60">
                    @if ($p['sisa'] > 0)
                        Kurang {{ number_format($p['sisa'], 0, ',', '.') }} {{ $p['satuan'] }} lagi.
                    @else
                        Syarat terpenuhi.
                    @endif
                </p>
            </div>
        @else
            <p class="mt-5 rounded-kartu border border-hijau/40 bg-hijau/10 px-4 py-3 text-sm text-hijau-tua">
                Anda sudah di peringkat tertinggi ({{ $labelPeringkat[$member->rank] ?? $member->rank }}).
            </p>
        @endif
    </section>

    {{-- Ringkasan angka --}}
    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($labelPoin as $kode => $label)
            <x-kartu-statistik
                :label="$label"
                :nilai="number_format($poin[$kode], 0, ',', '.')"
                :catatan="$kode === 'pa' ? 'dari jaringan' : ($kode === 'pr' ? 'dari belanja ulang' : 'dari kepemimpinan')" />
        @endforeach

        <x-kartu-statistik
            label="Hadiah menunggu"
            :nilai="number_format($status['menunggu_klaim'], 0, ',', '.')"
            catatan="perlu diambil di kantor"
            tone="{{ $status['menunggu_klaim'] > 0 ? 'proses' : 'netral' }}" />
    </div>

    {{-- Milestone berikutnya --}}
    @if ($status['milestone'])
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-kartu border border-amber/50 bg-amber/10 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-amber-900">Hadiah berikutnya: {{ $status['milestone']->name }}</p>
                <p class="text-xs text-amber-900/70">
                    Capai {{ number_format($status['milestone']->threshold, 0, ',', '.') }} poin total.
                    Sekarang {{ number_format($status['total_poin'], 0, ',', '.') }} poin.
                </p>
            </div>
            <x-badge tone="proses">
                kurang {{ number_format(max(0, $status['milestone']->threshold - $status['total_poin']), 0, ',', '.') }} poin
            </x-badge>
        </div>
    @endif

    {{-- Riwayat poin --}}
    <section class="mt-8" aria-labelledby="judul-riwayat">
        <h2 id="judul-riwayat" class="font-display text-lg">Riwayat poin</h2>

        @if ($riwayat->isEmpty())
            <div class="mt-3">
                <x-kosong judul="Belum ada poin yang tercatat"
                          pesan="Poin muncul setelah PIN ditukar atau transaksi tercatat." />
            </div>
        @else
            <div class="mt-3 overflow-x-auto rounded-kartu border border-garis bg-kertas">
                <table class="w-full text-sm">
                    <caption class="sr-only">Riwayat perolehan poin anggota</caption>
                    <thead class="border-b border-garis bg-krem/60 text-left text-xs uppercase tracking-wide text-tinta/60">
                        <tr>
                            <th scope="col" class="px-4 py-2 font-medium">Tanggal</th>
                            <th scope="col" class="px-4 py-2 font-medium">Jenis</th>
                            <th scope="col" class="px-4 py-2 text-right font-medium">Poin</th>
                            <th scope="col" class="px-4 py-2 font-medium">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-garis">
                        @foreach ($riwayat as $baris)
                            <tr class="hover:bg-krem/40">
                                <td class="whitespace-nowrap px-4 py-2 text-tinta/70">{{ $baris->created_at?->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-2">{{ $labelPoin[$baris->kind] ?? strtoupper($baris->kind) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums font-medium {{ $baris->delta > 0 ? 'text-hijau-tua' : 'text-red-700' }}">
                                    {{ $baris->delta > 0 ? '+' : '' }}{{ number_format($baris->delta, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 text-tinta/70">{{ $baris->note ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-paginasi :paginator="$riwayat" label="baris poin" />
        @endif
    </section>

    {{-- Hadiah --}}
    <section class="mt-8" aria-labelledby="judul-hadiah">
        <h2 id="judul-hadiah" class="font-display text-lg">Hadiah yang jadi hak Anda</h2>

        @if ($hadiah->isEmpty())
            <div class="mt-3">
                <x-kosong judul="Belum ada hadiah yang tercapai"
                          pesan="Hadiah terbuka saat total poin melewati ambang tertentu." />
            </div>
        @else
            <ul class="mt-3 divide-y divide-garis rounded-kartu border border-garis bg-kertas">
                @foreach ($hadiah as $klaim)
                    @php
                        $nadaKlaim = match ($klaim->status) {
                            'eligible' => 'proses',
                            'requested' => 'info',
                            'handed' => 'sukses',
                            'rejected' => 'bahaya',
                            default => 'netral',
                        };
                        $teksKlaim = match ($klaim->status) {
                            'eligible' => 'jadi hak Anda',
                            'requested' => 'sedang diproses',
                            'handed' => 'sudah diserahkan',
                            'rejected' => 'ditolak',
                            default => $klaim->status,
                        };
                    @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-medium">{{ $klaim->reward?->name ?? 'Hadiah' }}</p>
                            <p class="text-tinta/60">
                                ambang {{ number_format($klaim->reward?->threshold ?? 0, 0, ',', '.') }} poin
                                &middot; putaran {{ $klaim->cycle }}
                            </p>
                        </div>
                        <x-badge :tone="$nadaKlaim">{{ $teksKlaim }}</x-badge>
                    </li>
                @endforeach
            </ul>

            <p class="mt-2 text-xs text-tinta/60">
                Klaim diambil di kantor dengan menunjukkan bukti. Bawa nama hadiah dan ambangnya.
            </p>

            <x-paginasi :paginator="$hadiah" label="hadiah" />
        @endif
    </section>
@endsection
