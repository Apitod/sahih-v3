<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BonusEntry;
use App\Models\Transaction;
use Carbon\Carbon;

class FinansialController extends Controller
{
    public function index()
    {
        $awalBulan = now()->startOfMonth();
        $awalBulanLalu = now()->subMonthNoOverflow()->startOfMonth();
        $akhirBulanLalu = $awalBulan->copy()->subSecond();

        $omset = (int) Transaction::sum('amount');
        $omsetBulanIni = (int) Transaction::where('posted_at', '>=', $awalBulan)->sum('amount');
        $omsetBulanLalu = (int) Transaction::whereBetween('posted_at', [$awalBulanLalu, $akhirBulanLalu])->sum('amount');

        // Payout = bonus yang sudah jadi kewajiban (available) atau sudah dibayar (withdrawn).
        // pending belum cair, void batal → dua-duanya dikecualikan.
        $payoutQuery = fn () => BonusEntry::whereIn('status', ['available', 'withdrawn']);

        $payout = (int) $payoutQuery()->sum('amount');
        $payoutBulanIni = (int) $payoutQuery()->where('created_at', '>=', $awalBulan)->sum('amount');

        $perKind = $payoutQuery()
            ->groupBy('kind')
            ->selectRaw('kind, SUM(amount) as total, COUNT(*) as jumlah')
            ->get()->keyBy('kind');

        $distribusi = Transaction::groupBy('type')
            ->selectRaw('type, COUNT(*) as jumlah, SUM(amount) as total')
            ->get()->keyBy('type');

        $profit = $omset - $payout;
        $margin = $omset > 0 ? round($profit / $omset * 100, 1) : 0.0;

        $saldoBelumTarik = (int) BonusEntry::where('status', 'available')->sum('amount');

        return view('admin.finansial', compact(
            'omset', 'omsetBulanIni', 'omsetBulanLalu',
            'payout', 'payoutBulanIni', 'perKind', 'distribusi',
            'profit', 'margin', 'saldoBelumTarik'
        ));
    }
}
