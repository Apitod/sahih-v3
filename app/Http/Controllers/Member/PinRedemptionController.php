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

    public function create(Request $request): View
    {
        $member = Auth::guard('web')->user();

        $halamanRiwayat = max(1, (int) $request->query('riwayat', 1));

        return view('tukar', [
            // Hanya PIN yang bisa dipakai anggota: PIN RO, dan PIN gabungan yang
            // memang untuk naik peringkat (punya peringkat tujuan). PIN pendaftaran
            // dipakai admin saat mendaftarkan anggota baru, jadi tidak ditampilkan.
            'pinSaya' => Pin::where('assigned_member_id', $member->id)
                ->where('status', 'issued')
                ->where('is_free', false)
                ->where(function ($q) {
                    $q->where('type', Pin::JENIS_RO)
                        ->orWhere(fn ($q2) => $q2->where('type', Pin::JENIS_REGISTRASI_UPGRADE)
                            ->whereNotNull('target_rank'));
                })
                ->orderBy('type')
                ->get(),
            'riwayat' => Transaction::where('member_id', $member->id)
                ->with('pin')
                ->latest('id')
                ->paginate(8, ['*'], 'riwayat', $halamanRiwayat),
            'peringkat' => ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor', 'manager' => 'Manager'],
            'status' => [
                'aktif' => $member->status === 'active',
                'peringkat' => $member->rank,
                'jumlah_pin' => Pin::where('assigned_member_id', $member->id)
                    ->where('status', 'issued')
                    ->where('is_free', false)
                    ->where(function ($q) {
                        $q->where('type', Pin::JENIS_RO)
                            ->orWhere(fn ($q2) => $q2->where('type', Pin::JENIS_REGISTRASI_UPGRADE)
                                ->whereNotNull('target_rank'));
                    })
                    ->count(),
            ],
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

        // PIN naik peringkat WAJIB punya pemegang sah. PIN tanpa pemilik (assigned NULL)
        // tidak boleh ditebus siapa pun: si peneus akan menaikkan peringkatnya sendiri
        // hanya karena tahu kodepya (J1 audit 3 Nov). PIN seperti itu hanya sah sebagai
        // PIN pendaftaran, yang tidak dipakai di halaman ini.
        if ($pin->untukUpgrade() && $pin->assigned_member_id !== $member->id) {
            return back()->withInput()->withErrors(['code' => 'PIN ini bukan untuk akun Anda.']);
        }

        if ($pin->isRegistrasiUpgrade() && $pin->untukPendaftaran()) {
            return back()->withErrors(['code' => 'PIN ini untuk mendaftarkan anggota baru, bukan untuk akun yang sudah ada. Hubungi admin.']);
        }

        if ($pin->untukUpgrade()) {
            if ($this->ranks->weight($pin->target_rank) <= $this->ranks->weight($member->rank)) {
                $tujuan = ucfirst($pin->target_rank);

                return back()->withErrors([
                    'code' => "PIN ini untuk naik ke {$tujuan}, sedangkan akun Anda sudah {$this->label($member->rank)}. PIN tidak dipakai.",
                ]);
            }
        }

        try {
            $trx = $this->poster->post($member, $pin->id, $pin->isRo() ? 'ro' : 'upgrade');
        } catch (\RuntimeException $e) {
            // Jangan teruskan pesan mentah (mis. "status: used") ke anggota.
            report($e);

            return back()->withInput()->withErrors([
                'code' => 'PIN tidak bisa dipakai sekarang. Hubungi admin kalau menurut Anda ini keliru.',
            ]);
        }

        $pesan = $pin->untukUpgrade()
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
