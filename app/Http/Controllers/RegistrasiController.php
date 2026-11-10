<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Pin;
use App\Services\TransactionPoster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pendaftaran anggota baru lewat web.
 *
 * Aturan (BUSINESS_RULES §9–§10, DECISIONS #46):
 *  - PIN registrasi = `registrasi_upgrade` tanpa peringkat tujuan, 1/3/6 pin.
 *  - Kode referral opsional. Kosong → tanpa upline, bonus Rp 0. Diisi tapi
 *    tidak ditemukan → ditolak (jangan masuk jaringan orang lain).
 *  - Anggota + transaksi join dibuat atomik: gagal satu, batal semua.
 */
class RegistrasiController extends Controller
{
    public function __construct(private TransactionPoster $poster) {}

    public function create(): View
    {
        return view('daftar');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]*$/'],
            'name'     => ['required', 'string', 'max:100'],
            'phone'    => ['required', 'string', 'max:30'],
            'address'  => ['nullable', 'string', 'max:255'],
            'nik_ktp'  => ['required', 'string', 'digits:16', 'unique:members,nik_ktp'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'referral' => ['nullable', 'string', 'max:30'],
            'code'     => ['required', 'string', 'max:20'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.regex'    => 'Username hanya huruf, angka, titik, garis bawah, atau strip.',
            'nik_ktp.required'  => 'NIK KTP wajib diisi.',
            'nik_ktp.digits'    => 'NIK KTP harus tepat 16 digit angka.',
            'nik_ktp.unique'    => 'NIK KTP sudah terdaftar. Hubungi admin.',
            'password.confirmed' => 'Ulangi password harus sama.',
            'code.required'     => 'Kode PIN pendaftaran wajib diisi.',
        ]);

        $kode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $data['code']));
        $referral = trim((string) ($data['referral'] ?? ''));

        $pin = Pin::where('code', $kode)->first();

        if ($pin === null || ! $pin->isUsable() || ! $pin->untukPendaftaran()) {
            return back()->withInput()->withErrors(['code' => 'Kode PIN tidak dikenal atau sudah dipakai. Hubungi admin.']);
        }

        if ($pin->is_free && ! in_array($pin->target_rank, ['reseller', 'agent', 'distributor'], true)) {
            return back()->withInput()->withErrors(['code' => 'PIN gratis tidak memiliki peringkat target yang sah. Hubungi admin.']);
        }

        if (Member::where('username', $data['username'])->exists()) {
            return back()->withInput()->withErrors(['username' => 'Username sudah dipakai. Pilih yang lain.']);
        }

        $sponsor = null;
        if ($referral !== '') {
            $sponsor = Member::where('username', $referral)->first();

            // Referral diisi tapi tidak ditemukan → jangan diam-diam masuk jaringan lain (#46).
            if ($sponsor === null) {
                return back()->withInput()->withErrors(['referral' => 'Kode referral tidak ditemukan. Periksa lagi.']);
            }

            if ($sponsor->status !== 'active') {
                return back()->withInput()->withErrors(['referral' => 'Kode referral sudah tidak aktif.']);
            }

            // Semua pendaftaran: sponsor yang dipilih harus sudah punya poin PA.
            // Ini mencegah rantai abu-abu dari akun yang belum berkontribusi
            // (Owner 9 Nov).
            if ($sponsor->pointTotal('pa') <= 0) {
                return back()->withInput()->withErrors(['referral' => 'Sponsor yang dipilih belum memiliki poin PA. Pilih sponsor lain.']);
            }
        }

        try {
            $anggota = DB::transaction(function () use ($data, $pin, $sponsor) {
                // PIN gratis tidak menyimpan sponsor_id — hanya diverifikasi PA-nya
                // supaya tidak terjadi akun siluman masuk jaringan.
                $baru = Member::create([
                    'username'  => $data['username'],
                    'name'      => $data['name'],
                    'phone'     => $data['phone'],
                    'address'   => $data['address'] ?? null,
                    'nik_ktp'   => $data['nik_ktp'],
                    'password'  => $data['password'],
                    'sponsor_id' => $pin->is_free ? null : $sponsor?->id,
                    // Selalu mulai Reseller; TransactionPoster yang menaikkan ke
                    // target_rank (PIN gratis) supaya RankHistory tetap tercatat.
                    'rank'      => 'reseller',
                    'status'    => 'active',
                    'joined_at' => now(),
                ]);

                // Transaksi join: peringkat dari poin PIN, poin PR pribadi, PA+PR ke
                // sponsor, bonus sponsor, hadiah. Gagal satu = batal semua, PIN utuh.
                $this->poster->post($baru, $pin->id, 'join');

                return $baru;
            });
        } catch (\RuntimeException $e) {
            report($e);

            return back()->withInput()->withErrors(['code' => 'PIN tidak bisa dipakai sekarang. Hubungi admin.']);
        }

        Auth::guard('web')->login($anggota);

        return redirect()->route('beranda')->with('sukses', 'Selamat datang! Akun Anda aktif.');
    }
}
