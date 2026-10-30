<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Member;
use App\Models\MilestoneReward;
use App\Models\PointEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->admin();
        $this->hadiah();
        $this->anggotaContoh();
    }

    /** Akun admin pertama. Password acak, ditulis ke berkas 600 — tidak dicetak ke layar. */
    private function admin(): void
    {
        if (Admin::where('email', 'admin@sahih.local')->exists()) {
            return;
        }

        $password = Str::password(20, symbols: false);

        Admin::create([
            'name' => 'Admin Sahih',
            'email' => 'admin@sahih.local',
            'password' => Hash::make($password),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // HOME bisa kosong di cron/container: tanpa cadangan, path jadi '/.sahih-...'
        // dan password acak hilang setelah baris admin terlanjur dibuat.
        $isi = "email: admin@sahih.local\npassword: {$password}\n";
        $path = (getenv('HOME') ?: sys_get_temp_dir()).'/.sahih-admin-credentials';

        if (@file_put_contents($path, $isi) === false) {
            $path = sys_get_temp_dir().'/.sahih-admin-credentials';
            file_put_contents($path, $isi);
        }

        chmod($path, 0600);
        $this->command?->info("Kredensial admin ditulis ke {$path} (mode 600).");
    }

    /** Tabel hadiah dari BUSINESS_RULES.md §8. Nilai rupiah tidak disimpan di sini. */
    private function hadiah(): void
    {
        if (MilestoneReward::exists()) {
            return;
        }

        foreach ([[5, 'Produk'], [20, 'Produk'], [75, 'Handphone'], [500, 'Motor'], [3500, 'Mobil']] as $i => [$ambang, $nama]) {
            MilestoneReward::create([
                'threshold' => $ambang,
                'name' => $nama,
                'is_active' => true,
                'sort' => $i,
            ]);
        }
    }

    /** Anggota contoh untuk mencoba halaman. Bukan data sungguhan. */
    private function anggotaContoh(): void
    {
        if (Member::where('username', 'demo')->exists()) {
            return;
        }

        $sponsor = Member::create([
            'username' => 'sponsor',
            'name' => 'Sponsor Contoh',
            'phone' => '0800000000',
            'password' => Hash::make(Str::password(20, symbols: false)),
            'rank' => 'distributor',
            'joined_at' => now()->subMonths(2),
        ]);

        $demo = Member::create([
            'username' => 'demo',
            'name' => 'Anggota Contoh',
            'phone' => '0811111111',
            'password' => Hash::make('demo12345'),
            'sponsor_id' => $sponsor->id,
            'rank' => 'reseller',
            'joined_at' => now()->subDays(20),
        ]);

        foreach ([[1, 'pr', 'daftar'], [2, 'pr', 'repeat order'], [1, 'pa', 'sponsor langsung']] as $i => [$poin, $jenis, $catatan]) {
            PointEntry::create([
                'member_id' => $demo->id,
                'kind' => $jenis,
                'delta' => $poin,
                'source_type' => 'demo',
                'note' => $catatan,
                'created_at' => now()->subDays(10 - $i),
            ]);
        }

        $demo->update([
            'point_pr' => 3,
            'point_pa' => 1,
        ]);
    }
}
