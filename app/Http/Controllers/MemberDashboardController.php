<?php

namespace App\Http\Controllers;

use App\Models\MilestoneReward;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberDashboardController extends Controller
{
    /** Jumlah baris per halaman di beranda anggota. */
    private const PER_HALAMAN = 8;

    public function index(Request $request): View
    {
        $member = $request->user('web');

        $poin = [
            'pa' => $member->pointTotal('pa'),
            'pr' => $member->pointTotal('pr'),
            'pl' => $member->pointTotal('pl'),
        ];

        $totalPoin = array_sum($poin);

        // Halaman riwayat & hadiah memakai nama parameter berbeda supaya
        // membalik halaman riwayat tidak ikut menggeser halaman hadiah.
        $halamanRiwayat = max(1, (int) $request->query('riwayat', 1));
        $halamanHadiah = max(1, (int) $request->query('hadiah', 1));

        $riwayat = $member->pointEntries()
            ->latest('id')
            ->paginate(self::PER_HALAMAN, ['*'], 'riwayat', $halamanRiwayat);

        $hadiah = $member->milestoneClaims()
            ->with('reward')
            ->orderByDesc('cycle')
            ->paginate(self::PER_HALAMAN, ['*'], 'hadiah', $halamanHadiah);

        return view('beranda', [
            'member' => $member,
            // Angka di halaman ini selalu dari ledger, bukan kolom cache.
            'poin' => $poin,
            'riwayat' => $riwayat,
            'hadiah' => $hadiah,
            'sponsor' => $member->sponsor,
            'status' => $this->statusRingkas($member, $totalPoin),
        ]);
    }

    /**
     * Ringkasan status anggota untuk ditampilkan di beranda:
     * status akun, peringkat, progres ke peringkat berikutnya, dan milestone berikutnya.
     *
     * @return array<string, mixed>
     */
    private function statusRingkas($member, int $totalPoin): array
    {
        $urutan = ['reseller', 'agent', 'distributor', 'manager'];
        $indeks = array_search($member->rank, $urutan, true);
        $berikutnya = $indeks !== false && $indeks < count($urutan) - 1 ? $urutan[$indeks + 1] : null;

        // Ambang poin pendaftaran per peringkat: 1 = reseller, 3 = agent, 6 = distributor.
        $ambangPeringkat = (array) config('sahih.rank_by_points');
        $syaratManagerPa = (int) config('sahih.manager_min_pa');

        $progres = null;

        if ($berikutnya === 'manager') {
            // Manager tidak dijual lewat paket: syaratnya Distributor + 20 poin PA.
            $pa = (int) $member->pointTotal('pa');
            $progres = [
                'label' => 'Syarat menjadi Manager',
                'sekarang' => $pa,
                'target' => $syaratManagerPa,
                'satuan' => 'poin PA',
            ];
        } elseif ($berikutnya !== null) {
            $target = null;
            foreach ($ambangPeringkat as $poinPaket => $namaPeringkat) {
                if ($namaPeringkat === $berikutnya) {
                    $target = (int) $poinPaket;
                    break;
                }
            }

            if ($target !== null) {
                $progres = [
                    'label' => 'Menuju '.ucfirst($berikutnya),
                    'sekarang' => $totalPoin,
                    'target' => $target,
                    'satuan' => 'poin',
                ];
            }
        }

        if ($progres !== null) {
            $progres['persen'] = $progres['target'] > 0
                ? (int) min(100, round($progres['sekarang'] / $progres['target'] * 100))
                : 100;
            $progres['sisa'] = max(0, $progres['target'] - $progres['sekarang']);
        }

        // Milestone berikutnya: hadiah aktif terendah yang siklus ini belum diklaim.
        $sudahDiklaim = $member->milestoneClaims()->pluck('milestone_reward_id')->all();
        $milestoneBerikutnya = MilestoneReward::where('is_active', true)
            ->whereNotIn('id', $sudahDiklaim)
            ->orderBy('threshold')
            ->first();

        return [
            'aktif' => $member->status === 'active',
            'peringkat' => $member->rank,
            'berikutnya' => $berikutnya,
            'progres' => $progres,
            'total_poin' => $totalPoin,
            'milestone' => $milestoneBerikutnya,
            'menunggu_klaim' => $member->milestoneClaims()
                ->whereIn('status', ['eligible', 'requested'])
                ->count(),
            'transaksi' => Transaction::where('member_id', $member->id)->count(),
        ];
    }
}
