<?php

namespace App\Services;

use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\PointEntry;

class PencairanPl
{
    /**
     * Samakan poin PL dengan uang leadership yang sudah dibayar.
     * Idempoten: boleh dipanggil berkali-kali untuk withdrawal yang sama.
     * Pecahan poin dibulatkan KE BAWAH (maksimal rugi < 1 poin per anggota,
     * tidak menumpuk karena dihitung dari total kumulatif).
     */
    public function sinkron(Member $member, ?int $withdrawalId = null): int
    {
        // Serialisasi per anggota: dua pencairan berbeda bisa dibayar hampir
        // bersamaan. Tanpa lock, keduanya bisa membaca ledger yang sama dan
        // menulis potongan dobel.
        Member::whereKey($member->getKey())->lockForUpdate()->firstOrFail();

        $nilai = max(1, (int) config('sahih.leadership_value_per_point', 40000));

        // Bonus leadership ditandai `withdrawn` saat anggota MENGAJUKAN penarikan,
        // bukan saat admin membayarnya. Karena itu hanya hitung yang sudah `paid`;
        // baris lama tanpa withdrawal_id tetap dihitung sebagai warisan.
        $dibayar = (int) BonusEntry::where('member_id', $member->id)
            ->where('kind', 'leadership')
            ->where('status', 'withdrawn')
            ->where(function ($q) {
                $q->whereHas('withdrawal', fn ($w) => $w->where('status', 'paid'))
                    ->orWhereNull('withdrawal_id');
            })
            ->sum('amount');

        $seharusnya = intdiv($dibayar, $nilai);

        $sudah = -(int) PointEntry::where('member_id', $member->id)
            ->where('kind', 'pl')
            ->where('source_type', 'withdrawal')
            ->sum('delta');

        $selisih = $seharusnya - $sudah;

        if ($selisih <= 0) {
            return 0;
        }

        PointEntry::create([
            'member_id' => $member->id,
            'kind' => 'pl',
            'delta' => -$selisih,
            'source_type' => 'withdrawal',
            'source_id' => $withdrawalId,
            'note' => 'Poin PL dicairkan: Rp'.number_format($selisih * $nilai, 0, ',', '.'),
        ]);

        return $selisih;
    }
}
