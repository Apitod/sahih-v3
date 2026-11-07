<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BonusEntry;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaldoController extends Controller
{
    public function index()
    {
        $member = auth('web')->user();

        $saldoAvailable = (int) $member->bonusEntries()->where('status', 'available')->sum('amount');
        $totalBonus = (int) $member->bonusEntries()->sum('amount');
        $riwayatBonus = $member->bonusEntries()->latest('id')->paginate(20);
        $riwayatTarik = Withdrawal::where('member_id', $member->id)->latest()->paginate(10);

        return view('member.saldo', compact('saldoAvailable', 'totalBonus', 'riwayatBonus', 'riwayatTarik'));
    }

    public function create()
    {
        $member = auth('web')->user();
        $saldoAvailable = (int) $member->bonusEntries()->where('status', 'available')->sum('amount');

        return view('member.tarik', compact('saldoAvailable'));
    }

    public function store(Request $request)
    {
        $member = auth('web')->user();
        $saldoAvailable = (int) $member->bonusEntries()->where('status', 'available')->sum('amount');
        $min = (int) config('sahih.withdraw_min', 100000);

        $request->validate([
            'amount' => [
                'required', 'integer',
                "min:{$min}",
                "max:{$saldoAvailable}",
            ],
            'bank_name' => 'required|string|max:50',
            'account_number' => 'required|string|max:40',
            'account_holder' => 'required|string|max:100',
        ], [
            'amount.min' => "Minimum penarikan Rp " . number_format($min, 0, ',', '.') . ".",
            'amount.max' => "Jumlah melebihi saldo available.",
        ]);

        DB::transaction(function () use ($request, $member) {
            $withdrawal = Withdrawal::create([
                'member_id' => $member->id,
                'amount' => $request->amount,
                'bank_name' => $request->bank_name,
                'account_number' => $request->account_number,
                'account_holder' => $request->account_holder,
                'status' => 'requested',
                'requested_at' => now(),
            ]);

            // Mark bonus entries as withdrawn FIFO by id asc
            $remaining = (int) $request->amount;
            $entries = $member->bonusEntries()
                ->where('status', 'available')
                ->orderBy('id')
                ->get();

            foreach ($entries as $entry) {
                if ($remaining <= 0) break;
                if ($entry->amount > $remaining) {
                    // Pecah: sisa jadi baris baru yang tetap available; total bonus tidak berubah.
                    // Kolom generated (periode_kunci) tidak boleh ikut tersalin — DB yang menghitung.
                    $sisa = $entry->replicate(['periode_kunci']);
                    $sisa->amount = $entry->amount - $remaining;
                    $sisa->save();
                    $entry->amount = $remaining;
                }
                $remaining -= $entry->amount;
                $entry->status = 'withdrawn';
                $entry->withdrawal_id = $withdrawal->id;
                $entry->save();
            }
        });

        return redirect()->route('saldo')->with('sukses', 'Permintaan penarikan berhasil diajukan.');
    }
}
