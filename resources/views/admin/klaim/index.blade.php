@extends('layouts.dasar')

@section('judul', 'Klaim Hadiah')

@section('isi')
    <div class="flex items-center justify-between">
        <h1 class="font-display text-xl font-bold">Klaim Hadiah</h1>
        <form method="GET" action="{{ route('admin.klaim') }}">
            <label class="text-sm">Status
                <select name="status" onchange="this.form.submit()" class="ml-2 rounded border border-garis px-2 py-1">
                    <option value="">Semua</option>
                    @foreach ($status as $kunci => $label)
                        <option value="{{ $kunci }}" @selected($filter['status'] ?? '' === $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </div>

    <div class="mt-4 overflow-x-auto rounded-kartu border border-garis bg-kertas">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-garis text-xs text-tinta/60">
                <tr>
                    <th class="px-4 py-2">Anggota</th>
                    <th class="px-4 py-2">Hadiah</th>
                    <th class="px-4 py-2">Siklus</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Diajukan</th>
                    <th class="px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($klaim as $c)
                    <tr class="border-b border-garis/60">
                        <td class="px-4 py-2">{{ $c->member?->username }}</td>
                        <td class="px-4 py-2">{{ $c->reward?->name }}</td>
                        <td class="px-4 py-2">#{{ $c->cycle }}</td>
                        <td class="px-4 py-2">{{ $status[$c->status] ?? $c->status }}</td>
                        <td class="px-4 py-2">{{ $c->requested_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @if ($c->status === 'requested')
                                <form method="POST" action="{{ route('admin.klaim.serahkan', $c) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input name="note" placeholder="Catatan (opsional)" maxlength="255"
                                           class="w-40 rounded border border-garis px-2 py-1 text-xs">
                                    <button class="rounded bg-amber px-3 py-1 text-sm font-semibold text-tinta hover:bg-amber/90">Serahkan</button>
                                </form>
                            @else
                                <span class="text-tinta/40">{{ $c->handed_at?->format('d M Y') ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-tinta/50">Belum ada klaim.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $klaim->links() }}</div>
@endsection
