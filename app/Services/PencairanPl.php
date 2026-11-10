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
        $nilai = max(1, (int) config('sahih.leadership_value_per_point', 40000));

        $dibayar = (int) BonusEntry::where('member_id', $member->id)
            ->where('kind', 'leadership')
            ->where('status', 'withdrawn')
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
