<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::query()->with(['member', 'pin']);

        if ($jenis = $request->get('jenis')) {
            $query->where('type', $jenis);
        }

        if ($anggota = $request->get('anggota')) {
            $query->whereHas('member', fn($q) => $q->where('username', 'like', "%{$anggota}%"));
        }

        if ($dari = $request->get('dari')) {
            $query->whereDate('posted_at', '>=', $dari);
        }

        if ($sampai = $request->get('sampai')) {
            $query->whereDate('posted_at', '<=', $sampai);
        }

        $transaksi = $query->latest('posted_at')->latest('id')->paginate(20);

        return view('admin.transaksi.index', compact('transaksi'));
    }

    public function show(Transaction $transaksi)
    {
        $transaksi->load(['member', 'pin', 'bonuses.member']);

        return view('admin.transaksi.show', compact('transaksi'));
    }
}
