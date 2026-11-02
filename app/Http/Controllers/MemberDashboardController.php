<?php

namespace App\Http\Controllers;

use App\Models\Member;
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

        // Cek bonus (PRD v3.0 §7.2): rincian bonus sponsor yang sudah masuk.
        // Nominal rupiah (kolom amount) sengaja tidak diambil — §3.1 melarang
        // nilai rupiah tampil di halaman anggota.
        $bonus = $member->bonusEntries()
            ->select(['id', 'member_id', 'kind', 'percent_bp', 'level', 'status', 'available_at', 'created_at'])
            ->orderByDesc('id')
            ->paginate(self::PER_HALAMAN, ['*'], 'bonus');

        return view('beranda', [
            'member' => $member,
            // Angka di halaman ini selalu dari ledger, bukan kolom cache.
            'poin' => $poin,
            'riwayat' => $riwayat,
            'hadiah' => $hadiah,
            'bonus' => $bonus,
            'jaringan' => $this->jaringanTigaTingkat($member),
            'sponsor' => $member->sponsor,
            'status' => $this->statusRingkas($member, $totalPoin),
        ]);
    }

    /**
     * Pohon genealogi sampai tiga tingkat (PRD v3.0 §7.2, keputusan #39:
     * nama + peringkat saja). Dibatasi supaya beranda tidak menarik seluruh
     * jaringan ke memori.
     *
     * @return array<int, array{tingkat: int, anggota: \Illuminate\Support\Collection}>
     */
    private function jaringanTigaTingkat($member): array
    {
        $hasil = [];
        $sekarang = collect([$member]);

        for ($tingkat = 1; $tingkat <= 3; $tingkat++) {
            $id = $sekarang->pluck('id')->all();

            $sekarang = Member::whereIn('sponsor_id', $id)
                ->orderBy('username')
                ->get(['id', 'username', 'name', 'rank', 'sponsor_id']);

            if ($sekarang->isEmpty()) {
                break;
            }

            $hasil[] = ['tingkat' => $tingkat, 'anggota' => $sekarang];
        }

        return $hasil;
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

        // PRD v3.0 §3.3: peringkat ditentukan total PR, bukan total semua poin.
        // Ambangnya 1 = Reseller, 3 = Agent, 6 = Distributor.
        $ambangPeringkat = (array) config('sahih.rank_by_pr');
        $syaratManagerPa = (int) config('sahih.manager_min_pa');

        $pr = (int) $member->pointTotal('pr');
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
        } elseif ($berikutnya !== null && isset($ambangPeringkat[$berikutnya])) {
            $progres = [
                'label' => 'Menuju '.ucfirst($berikutnya),
                'sekarang' => $pr,
                'target' => (int) $ambangPeringkat[$berikutnya],
                'satuan' => 'poin PR',
            ];
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
