<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Pin;
use App\Models\Transaction;
use App\Services\RankResolver;
use App\Services\TransactionPoster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PinRedemptionController extends Controller
{
    public function __construct(
        private TransactionPoster $poster,
        private RankResolver $ranks,
    ) {}

    public function create(): View
    {
        $member = Auth::guard('web')->user();

        return view('tukar', [
            // PIN registrasi ditolak di halaman ini, jadi tidak perlu ditampilkan.
            'pinSaya' => Pin::where('assigned_member_id', $member->id)
                ->where('status', 'issued')
                ->where('type', '!=', 'registration')
                ->orderBy('type')
                ->get(),
            'riwayat' => Transaction::where('member_id', $member->id)
                ->with('pin')
                ->latest('id')
                ->limit(10)
                ->get(),
            'peringkat' => ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ], [
            'code.required' => 'Kode PIN belum diisi.',
        ]);

        $member = Auth::guard('web')->user();
        $kode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $data['code']));

        $pin = Pin::where('code', $kode)->first();

        if ($pin === null || ! $pin->isUsable() || ($pin->assigned_member_id !== null && $pin->assigned_member_id !== $member->id)) {
            return back()->withInput()->withErrors(['code' => 'Kode PIN tidak dikenal. Periksa lagi hurufnya.']);
        }

        if ($pin->type === 'registration') {
            return back()->withErrors(['code' => 'PIN registrasi dipakai admin saat mendaftarkan anggota baru, bukan di halaman ini.']);
        }

        if ($pin->type === 'upgrade') {
            if ($pin->target_rank === null) {
                return back()->withErrors(['code' => 'PIN ini tidak menyebut peringkat tujuan, jadi tidak bisa dipakai. Hubungi admin.']);
            }

            if ($this->ranks->weight($pin->target_rank) <= $this->ranks->weight($member->rank)) {
                $tujuan = ucfirst($pin->target_rank);

                return back()->withErrors([
                    'code' => "PIN ini untuk naik ke {$tujuan}, sedangkan akun Anda sudah {$this->label($member->rank)}. PIN tidak dipakai.",
                ]);
            }
        }

        try {
            $trx = $this->poster->post($member, $pin->id, $pin->type === 'upgrade' ? 'upgrade' : 'ro');
        } catch (\RuntimeException $e) {
            // Jangan teruskan pesan mentah (mis. "status: used") ke anggota.
            report($e);

            return back()->withInput()->withErrors([
                'code' => 'PIN tidak bisa dipakai sekarang. Hubungi admin kalau menurut Anda ini keliru.',
            ]);
        }

        $pesan = $pin->type === 'upgrade'
            ? "PIN dipakai. Peringkat Anda sekarang {$this->label($member->fresh()->rank)}."
            : 'PIN dipakai. Repeat order tercatat.';

        return redirect()->route('beranda')
            ->with('status', $pesan)
            ->with('status_trx', $trx->code);
    }

    private function label(string $rank): string
    {
        return ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'][$rank] ?? ucfirst($rank);
    }
}
