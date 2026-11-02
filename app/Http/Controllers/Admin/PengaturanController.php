<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\PengaturanHarga;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pengaturan angka yang boleh diubah Owner (PRD v3.0 §3.1).
 *
 * Hanya Superadmin yang boleh masuk: harga per pin, syarat Manager, dan
 * putaran hadiah mengubah perhitungan uang seluruh jaringan.
 */
class PengaturanController extends Controller
{
    /** Label ramah untuk tiap kunci, dipakai di form. */
    private const LABEL = [
        'harga_pin_registrasi' => ['Harga 1 pin registrasi / upgrade', 'Dipakai saat daftar anggota baru dan saat naik peringkat lewat PIN.', 'rupiah'],
        'harga_pin_ro' => ['Harga 1 pin repeat order (RO)', 'Dipakai saat anggota melakukan pembelian ulang.', 'rupiah'],
        'nilai_poin_leadership' => ['Nilai 1 poin leadership', 'Nilai internal. Tidak pernah ditampilkan ke anggota.', 'rupiah'],
        'syarat_manager_pa' => ['Syarat poin PA untuk jadi Manager', 'Setelah Distributor dan PA mencapai angka ini, status jadi Manager.', 'poin'],
        'putaran_hadiah' => ['Putaran hadiah (poin)', 'Setelah angka ini, hitungan hadiah mulai dari nol lagi.', 'poin'],
    ];

    public function index(): View
    {
        $this->hanyaSuperadmin();

        return view('admin.pengaturan', [
            'nilai' => app(PengaturanHarga::class)->semua(),
            'label' => self::LABEL,
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $this->hanyaSuperadmin();

        $aturan = [];
        foreach (array_keys(PengaturanHarga::BOLEH_DIUBAH) as $kunci) {
            $aturan[$kunci] = ['required', 'integer', 'min:0', 'max:1000000000'];
        }

        $data = $request->validate($aturan, [
            'required' => 'Angka ini belum diisi.',
            'integer' => 'Isi dengan angka bulat, tanpa titik atau koma.',
            'min' => 'Angka tidak boleh negatif.',
        ]);

        $admin = Auth::guard('admin')->user();
        $harga = app(PengaturanHarga::class);
        $sebelum = $harga->semua();

        foreach ($data as $kunci => $nilai) {
            $harga->simpan($kunci, (int) $nilai, $admin->id);
        }

        AuditLog::create([
            'actor_type' => 'admin',
            'actor_id' => $admin->id,
            'action' => 'pengaturan.update',
            'subject_type' => 'settings',
            'subject_id' => null,
            'before' => $sebelum,
            'after' => $harga->semua(),
            'ip' => $request->ip(),
        ]);

        return redirect()->route('admin.pengaturan')
            ->with('status', 'Pengaturan disimpan. Perubahan langsung berlaku untuk transaksi berikutnya.');
    }

    /** Hanya Superadmin. Admin biasa diarahkan kembali dengan pesan. */
    private function hanyaSuperadmin(): void
    {
        abort_unless(Auth::guard('admin')->user()?->isSuperadmin(), 403,
            'Hanya Superadmin yang boleh mengubah pengaturan.');
    }
}
