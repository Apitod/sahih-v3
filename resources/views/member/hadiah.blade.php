@extends('layouts.dasar')

@section('judul', 'Hadiah & Bonus — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-anggota />
@endsection

@section('topbar')
    <div>
        <h1 class="text-xl font-bold text-slate-900">Hadiah & Bonus</h1>
        <p class="text-sm text-slate-600">Milestone dan bonus sponsor</p>
    </div>
@endsection

@section('isi')
    @if (session('status') || session('sukses'))
        <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ session('status') ?? session('sukses') }}</div>
    @endif
    @if ($errors->has('claim'))
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first('claim') }}</div>
    @endif

    {{-- Target Milestone --}}
    <section aria-labelledby="judul-milestone">
        <h2 id="judul-milestone" class="text-lg font-bold text-slate-900">Target Milestone</h2>
        <p class="mt-1 text-sm text-slate-600">Hadiah terbuka saat total poin Anda melewati ambang ini.</p>

        @if ($milestones->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada milestone yang aktif</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <caption class="sr-only">Daftar target milestone</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Ambang Poin</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Nama Hadiah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($milestones as $m)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-900">{{ number_format($m->threshold, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-slate-900">{{ $m->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    {{-- Klaim Hadiah --}}
    <section class="mt-8" aria-labelledby="judul-klaim">
        <h2 id="judul-klaim" class="text-lg font-bold text-slate-900">Hadiah yang jadi hak Anda</h2>
        @if ($klaim->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada hadiah yang tercapai</p>
                <p class="mt-1 text-sm text-slate-500">Hadiah terbuka saat total poin melewati ambang tertentu.</p>
            </div>
        @else
            <ul class="mt-4 divide-y divide-slate-100 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                @foreach ($klaim as $c)
                    @php
                        $kelasKlaim = match ($c->status) {
                            'eligible' => 'bg-amber-100 text-amber-800',
                            'requested' => 'bg-blue-100 text-blue-800',
                            'handed' => 'bg-emerald-100 text-emerald-800',
                            default => 'bg-slate-100 text-slate-700',
                        };
                        $teksKlaim = match ($c->status) {
                            'eligible' => 'jadi hak Anda',
                            'requested' => 'sedang diproses',
                            'handed' => 'sudah diserahkan',
                            default => $c->status,
                        };
                    @endphp
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">{{ $c->reward?->name ?? 'Hadiah' }}</p>
                            <p class="text-slate-600">
                                ambang {{ number_format($c->reward?->threshold ?? 0, 0, ',', '.') }} poin · putaran #{{ $c->cycle }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $kelasKlaim }}">{{ $teksKlaim }}</span>
                            @if ($c->status === 'eligible')
                                <form method="POST" action="{{ route('hadiah.klaim') }}">
                                    @csrf
                                    <input type="hidden" name="claim_id" value="{{ $c->id }}">
                                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">Ajukan</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <x-paginasi :paginator="$klaim" label="hadiah" />
        @endif
    </section>

    {{-- Bonus Sponsor --}}
    <section class="mt-8" aria-labelledby="judul-bonus">
        <h2 id="judul-bonus" class="text-lg font-bold text-slate-900">Cek Bonus</h2>
        <p class="mt-1 text-sm text-slate-600">Bonus sponsor dari transaksi jaringan Anda. Nominal rupiah tidak ditampilkan.</p>

        @if ($bonus->isEmpty())
            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-slate-700">Belum ada bonus yang masuk</p>
                <p class="mt-1 text-sm text-slate-500">Bonus sponsor muncul setelah ada anggota di jaringan Anda yang bertransaksi.</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full text-sm">
                    <caption class="sr-only">Rincian bonus sponsor</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Jenis</th>
                            <th scope="col" class="px-4 py-3 text-right font-semibold">Persen</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Posisi</th>
                            <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($bonus as $b)
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $b->created_at->translatedFormat('j M Y') }}</td>
                                <td class="px-4 py-3 text-slate-900">{{ match ($b->kind) {
                                    'sponsor' => 'bonus sponsor',
                                    'differential' => 'selisih peringkat',
                                    'leadership' => 'leadership',
                                    default => $b->kind,
                                } }}</td>
                                <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ number_format($b->percent_bp / 100, 2, ',', '.') }}%</td>
                                <td class="px-4 py-3 text-slate-600">tingkat {{ $b->level }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ match ($b->status) {
                                        'available' => 'bg-emerald-100 text-emerald-700',
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'withdrawn' => 'bg-blue-100 text-blue-700',
                                        'void' => 'bg-red-100 text-red-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    } }}">{{ match ($b->status) {
                                        'available' => 'tersedia',
                                        'pending' => 'menunggu',
                                        'withdrawn' => 'dicairkan',
                                        'void' => 'dibatalkan',
                                        default => $b->status,
                                    } }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-paginasi :paginator="$bonus" label="bonus" />
        @endif
    </section>
@endsection
