<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BonusEntry;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'status' => 'required|in:approved,paid,rejected',
            'note' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $withdrawal) {
            // Kunci baris sebelum cek transisi: dua admin yang memproses bersamaan tidak
            // boleh sama-sama lolos validasi status yang sudah basi.
            $terkunci = Withdrawal::whereKey($withdrawal->getKey())->lockForUpdate()->firstOrFail();

            $allowed = match ($terkunci->status) {
                'requested' => ['approved', 'rejected'],
                'approved' => ['paid'],
                default => [],
            };

            if (! in_array($request->status, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status transition not allowed.',
                ]);
            }

            // Catatan baru wajib dicek setelah transisi sah, supaya penolakan yang memang
            // tidak boleh dilakukan tetap ditolak karena statusnya, bukan karena catatannya.
            if ($request->status === 'rejected' && trim((string) $request->note) === '') {
                throw ValidationException::withMessages([
                    'note' => 'Catatan wajib diisi untuk penolakan.',
                ]);
            }

            $terkunci->update([
                'status' => $request->status,
                'processed_by' => auth('admin')->id(),
                'processed_at' => now(),
                'note' => $request->note,
            ]);

            // Potong poin PL sejumlah dengan bonus leadership yang sudah dibayar.
            if ($request->status === 'paid') {
                app(\App\Services\PencairanPl::class)->sinkron($terkunci->member, $terkunci->id);
            }

            // Restore bonus entries if rejected
            if ($request->status === 'rejected') {
                BonusEntry::where('withdrawal_id', $terkunci->id)
                    ->update(['status' => 'available', 'withdrawal_id' => null]);
            }
        });

        return redirect()->route('admin.pencairan')->with('sukses', 'Status penarikan berhasil diperbarui.');
    }
}
