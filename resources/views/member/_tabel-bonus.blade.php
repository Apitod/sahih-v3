{{-- Tabel riwayat bonus, dipakai di /saldo dan /riwayat.
     Kolom "Sumber" menjawab "bonus ini dapat dari mana" — transaksi & anggota pembeli,
     atau periode PL untuk bonus leadership/RM. --}}
<div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <caption class="sr-only">Riwayat bonus anggota</caption>
        <thead class="border-b border-slate-200 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
            <tr>
                <th scope="col" class="px-4 py-3 font-semibold">Tanggal</th>
                <th scope="col" class="px-4 py-3 font-semibold">Jenis</th>
                <th scope="col" class="px-4 py-3 font-semibold">Sumber</th>
                <th scope="col" class="px-4 py-3 text-right font-semibold">Jumlah</th>
                <th scope="col" class="px-4 py-3 font-semibold">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($bonus as $b)
                <tr class="transition hover:bg-slate-50">
                    <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $b->created_at?->translatedFormat('j M Y') }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ match ($b->kind) {
                            'sponsor' => 'bg-blue-100 text-blue-700',
                            'differential' => 'bg-emerald-100 text-emerald-700',
                            'leadership' => 'bg-purple-100 text-purple-700',
                            default => 'bg-slate-100 text-slate-700',
                        } }}">{{ ucfirst($b->kind) }}</span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $b->sumber }}</td>
                    <td class="px-4 py-3 text-right tabular-nums font-semibold text-slate-900">Rp {{ number_format($b->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ match ($b->status) {
                            'available' => 'bg-emerald-100 text-emerald-700',
                            'withdrawn' => 'bg-slate-100 text-slate-600',
                            'pending' => 'bg-amber-100 text-amber-700',
                            default => 'bg-slate-100 text-slate-700',
                        } }}">{{ ucfirst($b->status) }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
