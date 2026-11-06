@extends('layouts.dasar')

@section('judul', 'Klaim Hadiah — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Klaim Hadiah</p>
    </div>
@endsection

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl tracking-tight">Klaim Hadiah</h1>
            <p class="mt-1 text-sm text-tinta/70">
                Ayunan dan hadiah milestone yang diklaim anggota. Yang diserahkan adalah produk, bukan uang.
            </p>
        </div>
        <form method="GET" action="{{ route('admin.klaim') }}" class="flex items-center gap-2">
            <label class="block">
                <span class="sr-only">Saring status klaim</span>
                <select name="status" onchange="this.form.submit()"
                        class="rounded-lg border border-garis bg-kertas px-3 py-2 text-sm focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                    <option value="">semua status</option>
                    @foreach ($status as $kunci => $label)
                        <option value="{{ $kunci }}" @selected(($filter['status'] ?? '') === $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <noscript>
                <button type="submit" class="rounded-lg border border-garis px-3 py-2 text-sm hover:bg-krem">Saring</button>
            </noscript>
        </form>
    </div>

    @if (session('status'))
        <p role="status"
           class="mt-4 rounded-kartu border border-hijau/40 bg-hijau/10 px-3 py-2 text-sm text-hijau-tua">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p role="alert"
           class="mt-4 rounded-kartu border border-bahaya-garis bg-bahaya-lembut px-3 py-2 text-sm text-bahaya">{{ $errors->first() }}</p>
    @endif

    <div class="mt-5 overflow-x-auto rounded-kartu border border-garis bg-kertas">
        <table class="w-full text-sm">
            <caption class="sr-only">Daftar klaim hadiah anggota</caption>
            <thead class="border-b border-garis bg-krem/60 text-left text-xs uppercase tracking-wide text-tinta/60">
                <tr>
                    <th scope="col" class="px-4 py-2 font-medium">Anggota</th>
                    <th scope="col" class="px-4 py-2 font-medium">Hadiah</th>
                    <th scope="col" class="px-4 py-2 font-medium">Siklus</th>
                    <th scope="col" class="px-4 py-2 font-medium">Status</th>
                    <th scope="col" class="px-4 py-2 font-medium">Diajukan</th>
                    <th scope="col" class="px-4 py-2 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-garis">
                @forelse ($klaim as $c)
                    <tr class="hover:bg-krem/40">
                        <td class="whitespace-nowrap px-4 py-2 font-medium">{{ $c->member?->username ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $c->reward?->name ?? '—' }}</td>
                        <td class="px-4 py-2 tabular-nums text-tinta/70">#{{ $c->cycle }}</td>
                        <td class="px-4 py-2">
                            <x-badge :tone="match ($c->status) {
                                'eligible' => 'netral',
                                'requested' => 'proses',
                                'handed' => 'sukses',
                                default => 'netral',
                            }">{{ $status[$c->status] ?? $c->status }}</x-badge>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2 text-tinta/70">{{ $c->requested_at?->translatedFormat('j M Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($c->status === 'requested')
                                <form method="POST" action="{{ route('admin.klaim.serahkan', $c) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="text" name="note" placeholder="Catatan (opsional)" maxlength="255" aria-label="Catatan serah terima"
                                           class="w-40 rounded-lg border border-garis bg-kertas px-2 py-1 text-xs focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                                    <button type="submit"
                                            class="rounded-lg bg-amber px-3 py-1.5 text-xs font-medium text-tinta transition hover:bg-amber/90 focus:outline-none focus:ring-2 focus:ring-amber/50">Serahkan</button>
                                </form>
                            @else
                                <span class="text-xs text-tinta/50">
                                    {{ $c->handed_at ? 'diserahkan '.$c->handed_at->translatedFormat('j M Y') : '—' }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-sm text-tinta/50">Belum ada klaim.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-paginasi :paginator="$klaim" label="klaim" />
@endsection
