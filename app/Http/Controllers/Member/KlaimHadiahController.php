<?php

namespace App\Http\Controllers\Member;

use App\Models\MilestoneClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Klaim hadiah dari sisi anggota (DECISIONS #34, BUSINESS_RULES §8).
 *
 * Hadiah jadi hak begitu ambang terlewati; klaim hanya mengubah status
 * `eligible` → `requested`. Poin tidak berkurang. Penyerahan oleh admin.
 */
class KlaimHadiahController
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'claim_id' => ['required', 'integer', 'exists:milestone_claims,id'],
        ]);

        $member = Auth::guard('web')->user();
        $klaim = MilestoneClaim::whereKey($data['claim_id'])->first();

        if ($klaim === null || $klaim->member_id !== $member->id) {
            return back()->withErrors(['claim' => 'Klaim ini bukan milik Anda.']);
        }

        if ($klaim->status !== 'eligible') {
            return back()->withErrors(['claim' => 'Klaim ini sudah dikelola.']);
        }

        $klaim->update([
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        return back()->with('sukses', 'Permintaan klaim terkirim. Ambil di kantor setelah admin konfirmasi.');
    }
}
