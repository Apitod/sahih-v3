<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BonusEntry;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PencairanAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Withdrawal::with('member')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $withdrawals = $query->paginate(20);

        return view('admin.pencairan.index', compact('withdrawals'));
    }

    public function proses(Request $request, Withdrawal $withdrawal)
    {
        $request->validate([
            'status' => [
                'required',
                'in:approved,paid,rejected',
                function ($attr, $val, $fail) use ($withdrawal) {
                    $allowed = match ($withdrawal->status) {
                        'requested' => ['approved', 'rejected'],
                        'approved' => ['paid'],
                        default => [],
                    };
                    if (!in_array($val, $allowed)) {
                        $fail('Status transition not allowed.');
                    }
                },
            ],
            'note' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
        ], ['note.required_if' => 'Catatan wajib diisi untuk penolakan.']);

        DB::transaction(function () use ($request, $withdrawal) {
            $withdrawal->update([
                'status' => $request->status,
                'processed_by' => auth('admin')->id(),
                'processed_at' => now(),
                'note' => $request->note,
            ]);

            // Restore bonus entries if rejected
            if ($request->status === 'rejected') {
                BonusEntry::where('withdrawal_id', $withdrawal->id)
                    ->update(['status' => 'available', 'withdrawal_id' => null]);
            }
        });

        return redirect()->route('admin.pencairan')->with('sukses', 'Status penarikan berhasil diperbarui.');
    }
}
