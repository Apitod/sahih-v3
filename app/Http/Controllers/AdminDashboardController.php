<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MilestoneClaim;
use App\Models\Pin;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    private const PER_HALAMAN = 10;

    public function index(Request $request): View
    {
        $transaksiTerakhir = Transaction::with('member')
            ->latest('id')
            ->paginate(self::PER_HALAMAN, ['*'], 'transaksi')
            ->withQueryString();

        return view('admin.beranda', [
            'jumlahAnggota' => Member::count(),
            'jumlahPinBelumDipakai' => Pin::where('status', 'issued')->count(),
            'jumlahTransaksi' => Transaction::count(),
            'transaksiTerakhir' => $transaksiTerakhir,
            'status' => $this->statusRingkas(),
        ]);
    }

    /**
     * Ringkasan kondisi sistem untuk ditampilkan sebagai status di dashboard.
     *
     * @return array<string, mixed>
     */
    private function statusRingkas(): array
    {
        $anggotaAktif = Member::where('status', 'active')->count();
        $anggotaBeku = Member::where('status', 'suspended')->count();

        // Sebaran peringkat anggota.
        $peringkat = [];
        foreach (['reseller', 'agent', 'distributor', 'manager'] as $rank) {
            $peringkat[$rank] = Member::where('rank', $rank)->count();
        }

        // PIN yang menunggu dipakai / sudah hangus.
        $pinIssued = Pin::where('status', 'issued')->count();
        $pinTerpakai = Pin::where('status', 'used')->count();
        $pinVoid = Pin::where('status', 'void')->count();

        // Klaim hadiah yang perlu ditindaklanjuti admin.
        $klaimMenunggu = MilestoneClaim::whereIn('status', ['eligible', 'requested'])->count();

        $totalAnggota = $anggotaAktif + $anggotaBeku;

        return [
            'anggota_aktif' => $anggotaAktif,
            'anggota_beku' => $anggotaBeku,
            'total_anggota' => $totalAnggota,
            'persen_aktif' => $totalAnggota > 0 ? (int) round($anggotaAktif / $totalAnggota * 100) : 0,
            'peringkat' => $peringkat,
            'pin' => [
                'issued' => $pinIssued,
                'used' => $pinTerpakai,
                'void' => $pinVoid,
            ],
            'klaim_menunggu' => $klaimMenunggu,
            'nilai_transaksi' => (int) Transaction::sum('amount'),
            'admin' => auth('admin')->user(),
        ];
    }
}
