<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Http\Request;

class AnggotaController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::query()->with('sponsor');

        if ($search = $request->get('q')) {
            $query->where(fn($q) => $q->where('username', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"));
        }

        if ($rank = $request->get('rank')) {
            $query->where('rank', $rank);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $anggota = $query->latest('id')->paginate(20);

        return view('admin.anggota.index', compact('anggota'));
    }

    public function show(Member $anggota)
    {
        $anggota->load('sponsor', 'downlines');

        $poin = [
            'pa' => $anggota->pointTotal('pa'),
            'pr' => $anggota->pointTotal('pr'),
            'pl' => $anggota->pointTotal('pl'),
        ];

        $pohon = $this->buildTree($anggota);

        return view('admin.anggota.show', compact('anggota', 'poin', 'pohon'));
    }

    public function edit(Member $anggota)
    {
        return view('admin.anggota.edit', compact('anggota'));
    }

    public function update(Request $request, Member $anggota)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,suspended',
        ]);

        $anggota->update($data);

        return back()->with('status', 'Data anggota berhasil diperbarui.');
    }

    /**
     * Hapus anggota. Hanya akun tanpa jejak: begitu ada poin, bonus, pencairan,
     * klaim hadiah, atau downline, barisnya tidak boleh hilang — cukup nonaktifkan
     * (status suspended) lewat Edit. Jejak audit tetap ditulis walau baris di-soft-delete.
     */
    public function destroy(Request $request, Member $anggota)
    {
        $alasan = match (true) {
            $anggota->downlines()->exists() => 'masih punya downline',
            $anggota->pointEntries()->exists() => 'sudah punya riwayat poin',
            $anggota->bonusEntries()->exists() => 'sudah punya riwayat bonus',
            \App\Models\Withdrawal::where('member_id', $anggota->id)->exists() => 'sudah pernah mengajukan pencairan',
            $anggota->milestoneClaims()->exists() => 'sudah pernah klaim hadiah',
            default => null,
        };

        if ($alasan !== null) {
            return back()->withErrors([
                'hapus' => "Tidak bisa dihapus: {$alasan}. Pakai Edit → Status akun → suspended.",
            ]);
        }

        AuditLog::create([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'action' => 'anggota.hapus',
            'subject_type' => Member::class,
            'subject_id' => $anggota->id,
            'before' => ['username' => $anggota->username, 'name' => $anggota->name],
            'after' => null,
            'ip' => $request->ip(),
        ]);

        $anggota->delete();

        return redirect()->route('admin.anggota.index')->with('status', "Anggota {$anggota->username} dihapus.");
    }

    /** Reset sandi oleh admin. Sandi baru tidak pernah dicatat di audit — hanya jejak aksinya. */
    public function resetPassword(Request $request, Member $anggota)
    {
        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $anggota->forceFill(['password' => bcrypt($data['password'])])->save();

        AuditLog::create([
            'actor_type' => 'admin',
            'actor_id' => auth('admin')->id(),
            'action' => 'anggota.reset_password',
            'subject_type' => Member::class,
            'subject_id' => $anggota->id,
            'before' => null,
            'after' => ['username' => $anggota->username],
            'ip' => $request->ip(),
        ]);

        return back()->with('status', 'Password anggota berhasil direset.');
    }

    private function buildTree($member, int $depth = 20)
    {
        $layers = [];
        $current = collect([$member]);

        for ($i = 0; $i < $depth; $i++) {
            $next = Member::whereIn('sponsor_id', $current->pluck('id'))
                ->orderBy('username')
                ->get(['id', 'username', 'name', 'rank', 'sponsor_id', 'status']);

            if ($next->isEmpty()) break;

            $layers[] = $next;
            $current = $next;
        }

        $childMap = [];
        foreach ($layers as $layer) {
            foreach ($layer as $m) {
                $childMap[$m->sponsor_id][] = $m;
            }
        }

        $plant = function (int $id, int $remaining) use (&$plant, $childMap) {
            return collect($childMap[$id] ?? [])->map(fn($child) => [
                'model' => $child,
                'anak' => $remaining > 0 ? $plant($child->id, $remaining - 1) : collect(),
            ]);
        };

        return $plant($member->id, $depth - 1);
    }
}
