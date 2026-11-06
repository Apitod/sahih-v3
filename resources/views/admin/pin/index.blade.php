@extends('layouts.dasar')

@section('judul', 'PIN — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Kelola PIN</p>
    </div>
@endsection

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl tracking-tight">PIN</h1>
            <p class="mt-1 text-sm text-tinta/70">Kode yang dijual ke anggota. Satu PIN dipakai satu kali.</p>
        </div>
        <a href="{{ route('admin.pin.create') }}"
           class="rounded-kartu bg-amber px-4 py-2 text-sm font-medium text-tinta transition hover:bg-amber/90 focus:outline-none focus:ring-2 focus:ring-amber/50">Buat PIN</a>
    </div>

    @if (session('status'))
        <p role="status"
           class="mt-4 rounded-kartu border border-hijau/40 bg-hijau/10 px-3 py-2 text-sm text-hijau-tua">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p role="alert"
           class="mt-4 rounded-kartu border border-bahaya-garis bg-bahaya-lembut px-3 py-2 text-sm text-bahaya">{{ $errors->first() }}</p>
    @endif

    <div class="mt-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-kartu border border-garis bg-kertas p-4">
            <p class="text-sm text-tinta/60">Belum dipakai</p>
            <p class="mt-1 font-display text-2xl tabular-nums">{{ number_format($ringkasan['issued'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-kartu border border-garis bg-kertas p-4">
            <p class="text-sm text-tinta/60">Sudah dipakai</p>
            <p class="mt-1 font-display text-2xl tabular-nums">{{ number_format($ringkasan['used'], 0, ',', '.') }}</p>
        </div>
        <div class="rounded-kartu border border-garis bg-kertas p-4">
            <p class="text-sm text-tinta/60">Dibatalkan</p>
            <p class="mt-1 font-display text-2xl tabular-nums">{{ number_format($ringkasan['void'], 0, ',', '.') }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.pin.index') }}" class="mt-6 flex flex-wrap items-end gap-3 text-sm">
        <label class="flex flex-col gap-1">
            <span class="text-tinta/60">Status</span>
            <select name="status" class="rounded-lg border border-garis bg-kertas px-3 py-2 text-sm focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                <option value="">semua</option>
                @foreach (['issued' => 'belum dipakai', 'used' => 'sudah dipakai', 'void' => 'dibatalkan'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(($filter['status'] ?? '') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1">
            <span class="text-tinta/60">Jenis</span>
            <select name="type" class="rounded-lg border border-garis bg-kertas px-3 py-2 text-sm focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                <option value="">semua</option>
                @foreach (['registrasi_upgrade' => 'registrasi / naik peringkat', 'ro' => 'repeat order'] as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(($filter['type'] ?? '') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="rounded-lg border border-garis px-3 py-2 text-sm hover:bg-krem">Saring</button>
    </form>

    @if ($pins->isEmpty())
        <div class="mt-6 max-w-md">
            <x-kosong judul="Belum ada PIN yang cocok"
                      pesan="Saring dengan status atau jenis lain, atau buat PIN baru." />
        </div>
    @else
        <div class="mt-4 overflow-x-auto rounded-kartu border border-garis bg-kertas">
            <table class="w-full text-sm">
                <caption class="sr-only">Daftar kode PIN beserta status dan pemegangnya</caption>
                <thead class="border-b border-garis bg-krem/60 text-left text-xs uppercase tracking-wide text-tinta/60">
                    <tr>
                        <th class="px-4 py-2 font-medium">Kode</th>
                        <th class="px-4 py-2 font-medium">Jenis</th>
                        <th class="px-4 py-2 font-medium text-right">Poin</th>
                        <th class="px-4 py-2 font-medium text-right">Harga</th>
                        <th class="px-4 py-2 font-medium">Pemegang</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                        <th class="px-4 py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-garis">
                    @foreach ($pins as $pin)
                        <tr class="hover:bg-krem/40">
                            <td class="px-4 py-2 font-mono text-xs tracking-wider">{{ $pin->code }}</td>
                            <td class="px-4 py-2">
                                {{ match (true) {
                                    $pin->isRo() => 'repeat order',
                                    $pin->untukUpgrade() => 'naik peringkat → '.($pin->target_rank ? ucfirst($pin->target_rank) : '?'),
                                    $pin->untukPendaftaran() => 'pendaftaran',
                                    default => $pin->type,
                                } }}
                            </td>
                            <td class="px-4 py-2 text-right tabular-nums">{{ number_format($pin->points, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-right tabular-nums">Rp {{ number_format($pin->price, 0, ',', '.') }}</td>
                            <td class="px-4 py-2 text-tinta/70">
                                {{ $pin->usedBy?->username ?? $pin->assignedMember?->username ?? '—' }}
                            </td>
                            <td class="px-4 py-2">
                                <span class="rounded-kartu px-2 py-0.5 text-xs {{ match ($pin->status) {
                                    'issued' => 'bg-krem text-tinta/70',
                                    'used' => 'bg-hijau/15 text-hijau-tua',
                                    'void' => 'bg-bahaya-lembut text-bahaya',
                                    default => 'bg-krem text-tinta/70',
                                } }}">{{ match ($pin->status) {
                                    'issued' => 'belum dipakai',
                                    'used' => 'dipakai',
                                    'void' => 'dibatalkan',
                                    default => $pin->status,
                                } }}</span>
                            </td>
                            <td class="px-4 py-2 text-right">
                                @if ($pin->status === 'issued')
                                    <form method="POST" action="{{ route('admin.pin.void', $pin) }}" class="flex items-center justify-end gap-2">
                                        @csrf
                                        <input type="text" name="reason" placeholder="alasan" aria-label="Alasan pembatalan PIN {{ $pin->code }}"
                                               class="w-28 rounded-lg border border-garis bg-kertas px-2 py-1 text-xs focus:border-hijau focus:ring-2 focus:ring-hijau/20">
                                        <button type="submit" class="rounded-lg border border-garis px-2 py-1 text-xs hover:bg-bahaya-lembut">Batalkan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-paginasi :paginator="$pins" label="PIN" />
    @endif
@endsection
