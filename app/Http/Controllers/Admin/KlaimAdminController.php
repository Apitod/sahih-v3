<?php

namespace App\Http\Controllers\Admin;

use App\Models\MilestoneClaim;
use App\Models\MilestoneReward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pengelolaan klaim hadiah dari sisi admin (BUSINESS_RULES §8).
 *
 * Admin melihat permintaan klaim dan menyerahkan hadiah (produk, bukan uang).
 * Nilai rupiah hadiah (value_internal) TIDAK pernah dikirim ke tampilan.
 */
class KlaimAdminController
{
    public function index(Request $request): View
    {
        $filter = $request->only(['status']);

        $klaim = MilestoneClaim::query()
            ->with(['member', 'reward'])
            ->when($filter['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.klaim.index', [
            'klaim' => $klaim,
            'filter' => $filter,
            'status' => [
                'eligible' => 'Jadi hak, belum diajukan',
                'requested' => 'Diminta anggota',
                'handed' => 'Sudah diserahkan',
            ],
        ]);
    }

    public function serahkan(Request $request, MilestoneClaim $klaim): RedirectResponse
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($klaim->status === 'handed') {
            return back()->withErrors(['klaim' => 'Klaim ini sudah diserahkan sebelumnya.']);
        }

        $klaim->update([
            'status' => 'handed',
            'handed_at' => now(),
            'handled_by' => Auth::guard('admin')->id(),
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->route('admin.klaim')->with('status', 'Hadiah ditandai sudah diserahkan.');
    }

    /** Hadiah aktif untuk referensi tampilan. */
    public static function hadiahAktif(): \Illuminate\Support\Collection
    {
        return MilestoneReward::where('is_active', true)->orderBy('sort')->get();
    }
}
