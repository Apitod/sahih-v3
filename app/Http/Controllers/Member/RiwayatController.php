<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $member = $request->user('web');

        $poin = $member->pointEntries()
            ->latest('id')
            ->paginate(15);

        $transaksi = Transaction::where('member_id', $member->id)
            ->with('pin')
            ->latest('posted_at')
            ->paginate(15, ['*'], 'trx');

        return view('member.riwayat', compact('poin', 'transaksi'));
    }
}
