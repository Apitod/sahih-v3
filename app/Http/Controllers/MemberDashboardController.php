<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $member = $request->user('web');

        return view('beranda', [
            'member' => $member,
            // Angka di halaman ini selalu dari ledger, bukan kolom cache.
            'poin' => [
                'pa' => $member->pointTotal('pa'),
                'pr' => $member->pointTotal('pr'),
                'pl' => $member->pointTotal('pl'),
            ],
            'riwayat' => $member->pointEntries()->latest('id')->limit(10)->get(),
            'hadiah' => $member->milestoneClaims()->with('reward')->orderByDesc('cycle')->limit(10)->get(),
            'sponsor' => $member->sponsor,
        ]);
    }
}
