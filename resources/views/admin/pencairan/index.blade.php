@extends('layouts.dasar')

@section('judul', 'Pencairan — Admin Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Pencairan Bonus</p>
    </div>
@endsection

@section('isi')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Pencairan Bonus</h1>
            <p class="mt-1 text-sm text-slate-600">Permintaan penarikan saldo anggota</p>
        </div>
        <form method="GET" action="{{ route('admin.pencairan') }}" class="flex items-center gap-2">
            <label class="block">
                <span class="sr-only">Saring status</span>
                <select name="status" onchange="this.form.submit()"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                    <option value="">Semua status</option>
                    <option value="requested" @selected(request('status') === 'requested')>Requested</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="paid" @selected(request('status') === 'paid')>Paid</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </select>
            </label>
            <noscript>
                <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-sm hover:bg-slate-50">Saring</button>
            </noscript>
        </form>
    </div>

    @php
        // ponytail: satu query hitung; pindah ke controller kalau nanti ada ringkasan lain.
        $menunggu = \App\Models\Withdrawal::where('status', 'requested')->count();
    @endphp
    @if ($menunggu > 0)
        <div data-menunggu-total="{{ $menunggu }}" class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>{{ $menunggu }} permintaan</strong> menunggu diproses. Transfer manual dulu sesuai nomor rekening, baru tandai <strong>Paid</strong>.
        </div>
    @endif

    @if (session('sukses'))
        <p role="status" class="mt-4 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('sukses') }}</p>
    @endif

    @if ($errors->any())
        <p role="alert" class="mt-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</p>
    @endif

    <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-sm">
            <caption class="sr-only">Daftar penarikan bonus anggota</caption>
            <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th scope="col" class="px-4 py-3 font-semibold">Anggota</th>
                    <th scope="col" class="px-4 py-3 text-right font-semibold">Jumlah</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Bank</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Rekening</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Diajukan</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($withdrawals as $w)
                    <tr class="transition hover:bg-slate-50">
                        <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $w->member?->username ?? '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-semibold">Rp {{ number_format($w->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $w->bank_name }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $w->account_number }}<br><span class="text-slate-500">{{ $w->account_holder }}</span></td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ match ($w->status) {
                                'requested' => 'bg-amber-100 text-amber-700',
                                'approved' => 'bg-blue-100 text-blue-700',
                                'paid' => 'bg-emerald-100 text-emerald-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                default => 'bg-slate-100 text-slate-700',
                            } }}">{{ ucfirst($w->status) }}</span>
                            @if ($w->note)
                                <br><span class="text-xs text-slate-500">{{ $w->note }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $w->requested_at->translatedFormat('j M Y H:i') }}</td>
                        <td class="px-4 py-3">
                            @if ($w->status === 'requested')
                                <form method="POST" action="{{ route('admin.pencairan.proses', $w) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    <input type="text" name="note" placeholder="Catatan" maxlength="500" aria-label="Catatan"
                                           class="w-32 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
                                    <button type="submit" name="status" value="approved"
                                            class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700">Approve</button>
                                    <button type="submit" name="status" value="rejected"
                                            class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-red-700">Reject</button>
                                </form>
                            @elseif ($w->status === 'approved')
                                <form method="POST" action="{{ route('admin.pencairan.proses', $w) }}" class="flex items-center gap-2">
                                    @csrf
                                    <input type="text" name="note" placeholder="Ref transfer" maxlength="500" aria-label="Referensi"
                                           class="w-32 rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20">
                                    <button type="submit" name="status" value="paid"
                                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-emerald-700">Mark Paid</button>
                                </form>
                            @else
                                <span class="text-xs text-slate-500">{{ $w->processed_at?->translatedFormat('j M Y') ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Belum ada penarikan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-paginasi :paginator="$withdrawals" label="penarikan" />
@endsection
