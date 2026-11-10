<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Pin;
use App\Services\PengaturanHarga;
use App\Services\RankResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Penerbitan PIN oleh admin.
 *
 * DECISIONS #73 (Owner 2 Nov): jenis PIN tinggal dua — `registrasi_upgrade` dan `ro`.
 * Satu PIN gabungan bisa dipakai untuk dua jalur, dibedakan oleh `target_rank`:
 *  - kosong  → mendaftarkan anggota baru (transaksi `join`)
 *  - terisi  → menaikkan peringkat anggota lama (transaksi `upgrade`)
 *
 * Harga dihitung sistem dari PengaturanHarga, tidak diketik admin. Admin tidak
 * bisa mengirim harga sendiri: kalau berbeda dari hitungan, permintaan ditolak.
 */
class PinController extends Controller
{
    /** Huruf yang mudah tertukar (O/0, I/1, L) dibuang supaya PIN bisa dibacakan lewat telepon. */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** Peringkat yang sah sebagai tujuan naik peringkat. Manager tidak dijual (#58). */
    private const PERINGKAT_TUJUAN = ['reseller', 'agent', 'distributor', 'manager'];

    /** Peringkat yang boleh dipilih untuk PIN gratis (Owner 9 Nov). */
    private const PERINGKAT_GRATIS = ['reseller', 'agent', 'distributor'];

    public function index(Request $request): View
    {
        $filter = $request->only(['status', 'type']);

        $pins = Pin::query()
            ->when($filter['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filter['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->with(['assignedMember', 'usedBy'])
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.pin.index', [
            'pins' => $pins,
            'filter' => $filter,
            'ringkasan' => [
                'issued' => Pin::where('status', 'issued')->count(),
                'used' => Pin::where('status', 'used')->count(),
                'void' => Pin::where('status', 'void')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $harga = app(PengaturanHarga::class);

        return view('admin.pin.create', [
            // Reseller disertakan karena PIN gratis boleh menarget Reseller.
            'peringkat' => ['reseller' => 'Reseller', 'agent' => 'Agent', 'distributor' => 'Distributor'],
            // Harga per pin dari pengaturan, supaya Owner bisa mengubah tanpa sentuh kode.
            'hargaRegistrasi' => $harga->ambil('harga_pin_registrasi'),
            'hargaRo' => $harga->ambil('harga_pin_ro'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', Pin::JENIS)],
            // PIN gratis tidak butuh isian poin/harga: server memaksa 0.
            'points' => ['required_unless:is_free,1', 'nullable', 'integer', 'min:0', 'max:1000'],
            // Harga tetap diterima supaya PIN lama/klien lama tidak pecah, tapi
            // nilainya WAJIB sama dengan hitungan sistem di bawah.
            'price' => ['required_unless:is_free,1', 'nullable', 'integer', 'min:0', 'max:1000000000'],
            'target_rank' => ['nullable', 'in:'.implode(',', self::PERINGKAT_TUJUAN)],
            'is_free' => ['nullable', 'boolean'],
            'quantity' => ['required', 'integer', 'min:1', 'max:50'],

            // Verifikasi username dua kali (permintaan Owner). `username_confirmation`
            // dibuat wajib hanya ketika `username` terisi, supaya tidak bisa dikosongkan
            // begitu saja lewat jalur naik peringkat.
            'username' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]*$/'],
            'username_confirmation' => ['nullable', 'string', 'max:30', 'required_with:username'],
        ], [
            'type.in' => 'Jenis PIN hanya ada dua: registrasi/upgrade, atau RO.',
            'target_rank.in' => 'Peringkat tujuan tidak dikenal.',
            'username_confirmation.required_with' => 'Ketik ulang username untuk memastikan tidak salah.',
        ]);

        // ---------------------------------------------------------------- 1. username dua kali
        $username = trim((string) ($data['username'] ?? ''));
        $ulang = trim((string) ($data['username_confirmation'] ?? ''));

        // Dua kolom harus sama. Kalau hanya satu yang diisi, yang kosong itu yang disalahkan,
        // supaya pesan errornya menunjuk kolom yang perlu dilengkapi.
        if ($username !== '' && $ulang === '') {
            return back()->withInput()->withErrors([
                'username_confirmation' => 'Ketik ulang username untuk memastikan tidak salah.',
            ]);
        }

        if ($username === '' && $ulang !== '') {
            return back()->withInput()->withErrors([
                'username' => 'Username belum diisi, padahal ketikan ulangnya ada.',
            ]);
        }

        // Dua kolom harus sama. Bandingan dipakai case-insensitive (strcasecmp),
        // karena pencarian username di database memakai kolasi ci pada MySQL/MariaDB
        // (`utf8mb4_unicode_ci`). Kalau bandingannya case-sensitive, 'BudiSahih'
        // vs 'budisahih' ditolak padahal keduanya menunjuk anggota yang sama.
        if ($username !== '' && strcasecmp($username, $ulang) !== 0) {
            return back()->withInput()->withErrors([
                'username' => 'Username dan ketikan ulang tidak sama. Periksa lagi — PIN salah pemegang berarti salah harga.',
            ]);
        }

        // ---------------------------------------------------------------- 2. siapa pemegangnya
        $member = null;

        if ($username !== '') {
            $member = Member::where('username', $username)->first();

            if ($member !== null && $member->status !== 'active') {
                return back()->withInput()->withErrors(['username' => 'Anggota ini sedang dibekukan.']);
            }
        }

        // ---------------------------------------------------------------- 3. PIN gratis
        $isFree = ! empty($data['is_free']);

        if ($isFree) {
            if ($data['type'] !== Pin::JENIS_REGISTRASI_UPGRADE) {
                return back()->withInput()->withErrors([
                    'type' => 'PIN gratis hanya untuk jalur registrasi/upgrade.',
                ]);
            }

            $target = ($data['target_rank'] ?? null) ?: null;

            if ($target === null || ! in_array($target, self::PERINGKAT_GRATIS, true)) {
                return back()->withInput()->withErrors([
                    'target_rank' => 'PIN gratis wajib memilih peringkat target: Reseller, Agent, atau Distributor.',
                ]);
            }

            // PIN gratis selalu tanpa pemegang: dipakai orang baru saat /daftar.
            // Kalau dititipkan (assigned_member_id terisi), TransactionPoster menolak
            // karena buyer baru ≠ pemegang lama — PIN jadi bata.
            if ($username !== '') {
                return back()->withInput()->withErrors([
                    'username' => 'PIN gratis tidak boleh menyebut username — dipakai anggota baru saat pendaftaran.',
                ]);
            }

            // PIN gratis: poin dan harga dipaksa nol, tidak ada perbandingan harga.
            $data['points'] = 0;
            $data['price'] = 0;
        }

        // ---------------------------------------------------------------- 4. jalur: daftar atau naik?
        $target = ($data['target_rank'] ?? null) ?: null;

        // Manager tidak dijual lewat PIN (#58).
        if ($target === 'manager') {
            return back()->withInput()->withErrors([
                'target_rank' => 'Manager tidak dijual lewat PIN — Manager otomatis setelah Distributor + 20 poin PA.',
            ]);
        }

        // Reseller adalah peringkat awal; tidak ada anggota yang bisa naik ke situ (#55).
        // PIN gratis boleh menarget Reseller karena dipakai untuk pendaftaran.
        if ($target === 'reseller' && ! $isFree) {
            return back()->withInput()->withErrors([
                'target_rank' => 'PIN naik peringkat tidak bisa ke Reseller — Reseller adalah peringkat awal.',
            ]);
        }

        // Peringkat tujuan hanya bermakna kalau PIN memang untuk naik peringkat, dan
        // itu butuh pemegang yang SUDAH terdaftar: peringkat tujuan harus di atas
        // peringkat sekarang. Kalau username diketik tapi tidak ada di database,
        // admin salah ketik — ditolak, jangan sampai PIN terbit tanpa pemilik.
        if ($target !== null && $member === null && ! $isFree) {
            $pesan = $username === ''
                ? 'PIN naik peringkat harus menyebut pemegangnya, supaya peringkat tujuannya bisa diperiksa.'
                : "Username \"{$username}\" belum terdaftar. Untuk naik peringkat, anggotanya harus sudah punya akun. Periksa lagi ejaannya.";

            return back()->withInput()->withErrors(['username' => $pesan]);
        }

        // PIN pendaftaran yang username-nya diketik tapi sudah terdaftar: PIN tetap
        // boleh terbit dan dititipkan ke anggota itu. Tidak ditolak — admin mungkin
        // menyiapkan PIN untuk anggota lama yang akan didaftarkan ulang setelah
        // akunnya bermasalah. Yang penting PIN-nya tidak bisa dipakai di /tukar
        // (ditolak di PinRedemptionController), jadi tidak menimbulkan jalur ganda.

        if ($target !== null && $member !== null && ! $isFree) {
            $ranks = app(RankResolver::class);

            if ($ranks->weight($target) <= $ranks->weight($member->rank)) {
                return back()->withInput()->withErrors([
                    'target_rank' => "Anggota ini sudah {$member->rank}. PIN naik peringkat harus ke peringkat di atasnya.",
                ]);
            }
        }

        // ---------------------------------------------------------------- 5. harga dari sistem
        $harga = app(PengaturanHarga::class);
        $points = (int) $data['points'];

        if (! $isFree) {
            if ($data['type'] === Pin::JENIS_RO) {
                $expectedPrice = $points * $harga->ambil('harga_pin_ro');

                if ((int) $data['price'] !== $expectedPrice) {
                    return back()->withInput()->withErrors([
                        'price' => "PIN RO {$points} poin harus Rp ".number_format($expectedPrice, 0, ',', '.').'.',
                    ]);
                }
            } else {
                // PIN gabungan. Dua jalur dengan aturan jumlah pin berbeda:
                //
                //  - Pendaftaran (tanpa peringkat tujuan): jumlah pin HARUS salah satu
                //    paket 1/3/6, karena peringkat anggota baru ditentukan dari situ.
                //  - Naik peringkat (dengan peringkat tujuan): jumlah pin bebas, karena
                //    peringkatnya sudah ditunjuk admin. Yang penting harganya = pin × harga.
                //    Contoh: Reseller -> Distributor butuh 5 pin (6 - 1).
                $paket = array_map('intval', array_keys((array) config('sahih.rank_by_points')));

                if ($target === null && ! in_array($points, $paket, true)) {
                    return back()->withInput()->withErrors([
                        'points' => 'Untuk PIN pendaftaran, jumlah pin harus salah satu paket: '.implode(', ', $paket).'.',
                    ]);
                }

                // PIN naik peringkat harus berisi minimal 1 pin. PIN 0 pin hanya bisa
                // lahir lewat data lama yang salah; panel tidak boleh menerbitkannya
                // lagi, karena pasti ditolak saat dipakai (ada uji penjaganya).
                if ($target !== null && $points < 1) {
                    return back()->withInput()->withErrors([
                        'points' => 'PIN naik peringkat harus berisi minimal 1 pin.',
                    ]);
                }

                $expectedPrice = $points * $harga->ambil('harga_pin_registrasi');

                if ((int) $data['price'] !== $expectedPrice) {
                    return back()->withInput()->withErrors([
                        'price' => "PIN {$points} pin harus Rp ".number_format($expectedPrice, 0, ',', '.')
                            .' ('.$points.' × Rp '.number_format($harga->ambil('harga_pin_registrasi'), 0, ',', '.').').',
                    ]);
                }
            }
        }

        // ---------------------------------------------------------------- 6. simpan
        $admin = Auth::guard('admin')->user();
        $dibuat = [];

        DB::transaction(function () use ($data, $member, $target, $isFree, $username, $admin, &$dibuat) {
            for ($i = 0; $i < (int) $data['quantity']; $i++) {
                $pin = Pin::create([
                    'code' => $this->kodeUnik(),
                    'type' => $data['type'],
                    'target_rank' => $target,
                    'points' => (int) $data['points'],
                    'price' => (int) $data['price'],
                    'is_free' => $isFree,
                    'status' => 'issued',
                    'issued_by' => $admin->id,
                    'assigned_member_id' => $member?->id,
                ]);

                AuditLog::create([
                    'actor_type' => 'admin',
                    'actor_id' => $admin->id,
                    'action' => 'pin.create',
                    'subject_type' => Pin::class,
                    'subject_id' => $pin->id,
                    'after' => $pin->only(['code', 'type', 'target_rank', 'points', 'price', 'is_free', 'assigned_member_id'])
                        + ['username_diketik' => $username],
                    'ip' => request()->ip(),
                ]);

                $dibuat[] = $pin;
            }
        });

        return redirect()->route('admin.pin.index')
            ->with('status', count($dibuat).' PIN dibuat.');
    }

    public function void(Request $request, Pin $pin): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);

        if ($pin->status !== 'issued') {
            return back()->withErrors(['reason' => 'Hanya PIN yang belum dipakai bisa dibatalkan.']);
        }

        $admin = Auth::guard('admin')->user();
        $sebelum = $pin->only(['status']);

        DB::transaction(function () use ($pin, $admin, $data, $sebelum) {
            $pin->update(['status' => 'void']);

            AuditLog::create([
                'actor_type' => 'admin',
                'actor_id' => $admin->id,
                'action' => 'pin.void',
                'subject_type' => Pin::class,
                'subject_id' => $pin->id,
                'before' => $sebelum,
                'after' => ['status' => 'void', 'reason' => $data['reason']],
                'ip' => request()->ip(),
            ]);
        });

        return redirect()->route('admin.pin.index')->with('status', 'PIN dibatalkan.');
    }

    private function kodeUnik(): string
    {
        do {
            $kode = '';
            for ($i = 0; $i < 12; $i++) {
                $kode .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (Pin::where('code', $kode)->exists());

        return $kode;
    }
}
