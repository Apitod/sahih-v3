@extends('layouts.dasar')

@section('judul', 'Beranda — Smart Store Sahih')

@section('nav')
    <span class="text-tinta/70">{{ $member->username }}</span>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="rounded-kartu border border-garis px-3 py-1.5 hover:bg-krem">Keluar</button>
    </form>
@endsection

@section('isi')
    @php
        $labelPeringkat = match ($member->rank) {
            'reseller' => 'Reseller',
            'agent' => 'Agent',
            'distributor' => 'Distributor',
            'manager' => 'Manager',
            default => ucfirst($member->rank),
        };
        $labelPoin = ['pa' => 'Poin Afiliasi', 'pr' => 'Poin Reward', 'pl' => 'Poin Leadership'];
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl">Halo, {{ $member->name }}</h1>
            <p class="mt-1 text-sm text-tinta/70">
                {{ $sponsor ? 'Di bawah sponsor '.$sponsor->username : 'Terdaftar tanpa sponsor' }}
                &middot; bergabung {{ $member->joined_at?->translatedFormat('j F Y') }}
            </p>
        </div>
        <span class="rounded-kartu border border-hijau/40 bg-hijau/10 px-3 py-1.5 text-sm font-medium text-hijau-tua">
            {{ $labelPeringkat }}
        </span>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        @foreach ($labelPoin as $kode => $label)
            <div class="rounded-kartu border border-garis bg-kertas p-4">
                <p class="text-sm text-tinta/60">{{ $label }}</p>
                <p class="mt-1 font-display text-3xl tabular-nums">{{ number_format($poin[$kode], 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    <section class="mt-8">
        <h2 class="font-display text-lg">Riwayat poin</h2>
        @if ($riwayat->isEmpty())
            <p class="mt-2 text-sm text-tinta/60">Belum ada poin yang tercatat.</p>
        @else
            <div class="mt-3 overflow-hidden rounded-kartu border border-garis bg-kertas">
                <table class="w-full text-sm">
                    <thead class="border-b border-garis bg-krem/60 text-left text-xs uppercase tracking-wide text-tinta/60">
                        <tr>
                            <th class="px-4 py-2 font-medium">Tanggal</th>
                            <th class="px-4 py-2 font-medium">Jenis</th>
                            <th class="px-4 py-2 font-medium text-right">Poin</th>
                            <th class="px-4 py-2 font-medium">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-garis">
                        @foreach ($riwayat as $baris)
                            <tr>
                                <td class="px-4 py-2 text-tinta/70">{{ $baris->created_at?->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-2">{{ $labelPoin[$baris->kind] ?? strtoupper($baris->kind) }}</td>
                                <td class="px-4 py-2 text-right tabular-nums">
                                    {{ $baris->delta > 0 ? '+' : '' }}{{ number_format($baris->delta, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-2 text-tinta/70">{{ $baris->note ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="mt-8">
        <h2 class="font-display text-lg">Hadiah yang jadi hak Anda</h2>
        @if ($hadiah->isEmpty())
            <p class="mt-2 text-sm text-tinta/60">Belum ada hadiah yang tercapai.</p>
        @else
            <ul class="mt-3 divide-y divide-garis rounded-kartu border border-garis bg-kertas">
                @foreach ($hadiah as $klaim)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ $klaim->reward?->name ?? 'Hadiah' }}</p>
                            <p class="text-tinta/60">
                                ambang {{ number_format($klaim->reward?->threshold ?? 0, 0, ',', '.') }} poin
                                &middot; putaran {{ $klaim->cycle }}
                            </p>
                        </div>
                        <span class="rounded-kartu bg-krem px-2.5 py-1 text-xs text-tinta/70">
                            {{ match ($klaim->status) {
                                'eligible' => 'jadi hak Anda',
                                'requested' => 'sedang diproses',
                                'handed' => 'sudah diserahkan',
                                default => $klaim->status,
                            } }}
                        </span>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-tinta/60">Klaim diambil di kantor dengan menunjukkan bukti. Bawa nama hadiah dan ambangnya.</p>
        @endif
    </section>
@endsection
