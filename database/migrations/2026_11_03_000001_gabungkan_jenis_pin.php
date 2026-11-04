<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DECISIONS #73 (Owner 2 Nov): jenis PIN dari tiga menjadi dua.
 *
 *   registration → registrasi_upgrade
 *   upgrade      → registrasi_upgrade
 *   ro           → ro (tetap)
 *
 * Kolom `type` diubah dari ENUM menjadi STRING. Alasannya: ENUM di MySQL/MariaDB
 * harus diubah dengan `ALTER TABLE ... MODIFY`, sedangkan SQLite (dipakai uji)
 * tidak mendukungnya sama sekali. STRING jalan di keduanya, dan daftar jenis yang
 * sah tetap dijaga di level aplikasi lewat Pin::JENIS.
 *
 * Kolom `target_rank` TIDAK dihapus: sejak jenisnya bergabung, kolom inilah yang
 * membedakan PIN pendaftaran (kosong) dari PIN naik peringkat (terisi).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Longgarkan kolom supaya nilai baru bisa masuk.
        Schema::table('pins', function (Blueprint $table) {
            $table->string('type', 30)->change();
        });

        // 2. Tentukan peringkat tujuan untuk PIN `upgrade` lama yang belum punya,
        //    SELAGI status `upgrade`-nya masih terlihat. Dari HARGA yang dibayar
        //    (harga / harga per pin = jumlah pin), bukan dari kolom `points` yang
        //    untuk PIN upgrade lama selalu 0.
        $this->isiTargetRankPinUpgradeDariHarga();

        // 3. Tentukan peringkat tujuan untuk PIN yang SUDAH DIPAKAI, dari transaksinya
        //    (peringkat pembeli setelah transaksi itulah peringkat tujuannya).
        $this->isiTargetRankDariTransaksi();

        // 4. Pindahkan data lama ke jenis baru.
        DB::table('pins')->where('type', 'registration')->update(['type' => 'registrasi_upgrade']);
        DB::table('pins')->where('type', 'upgrade')->update(['type' => 'registrasi_upgrade']);

        // 5. Isi ulang `points` dan `price` supaya ekuivalen dengan aturan baru
        //    (harga = jumlah pin × harga per pin). PIN lama yang sudah dibayar
        //    pelanggan tidak boleh jadi bangkai (K2 audit 3 Nov).
        $this->selaraskanPoinDanHarga();
    }

    /**
     * PIN upgrade lama diterbitkan dengan `points = 0` dan `price = selisih paket`.
     * Jumlah pin yang dibayar = price / harga per pin; dari situ peringkat tujuannya
     * bisa ditebak dengan aman:
     *   - price 1.200.000 → 2 pin → Agent      (Reseller -> Agent)
     *   - price 1.800.000 → 3 pin → Distributor (Agent -> Distributor)
     *   - price 3.000.000 → 5 pin → Distributor (Reseller -> Distributor)
     *   - price 3.600.000 → 6 pin → Distributor
     *   - price 600.000 (1 pin) → dilewati: hasilnya Reseller, bukan tujuan sah.
     * Yang tidak cocok dibiarkan kosong — lebih aman PIN jadi pendaftaran daripada
     * salah menaikkan peringkat orang.
     */
    private function isiTargetRankPinUpgradeDariHarga(): void
    {
        $hargaPerPin = (int) (config('sahih.registration_price_per_point') ?: 600000);

        if ($hargaPerPin < 1) {
            return;
        }

        $baris = DB::table('pins')
            ->where('type', 'upgrade')
            ->whereNull('target_rank')
            ->where('status', 'issued')
            ->get(['id', 'price']);

        foreach ($baris as $pin) {
            $jumlahPin = intdiv((int) $pin->price, $hargaPerPin);

            $peringkat = match ($jumlahPin) {
                2 => 'agent',
                3, 5, 6 => 'distributor',
                default => null,
            };

            if ($peringkat === null) {
                continue;
            }

            DB::table('pins')->where('id', $pin->id)->update(['target_rank' => $peringkat]);
        }
    }

    /** Ambil peringkat tujuan dari transaksi upgrade yang memakai PIN itu. */
    private function isiTargetRankDariTransaksi(): void
    {
        $ids = DB::table('pins')
            ->join('transactions', 'transactions.pin_id', '=', 'pins.id')
            ->where('pins.type', 'registrasi_upgrade')
            ->whereNull('pins.target_rank')
            ->where('transactions.type', 'upgrade')
            ->distinct()
            ->pluck('pins.id');

        foreach ($ids as $id) {
            $trx = DB::table('transactions')
                ->where('pin_id', $id)
                ->where('type', 'upgrade')
                ->first();

            if ($trx === null) {
                continue;
            }

            $member = DB::table('members')->where('id', $trx->member_id)->first();

            if ($member !== null) {
                DB::table('pins')->where('id', $id)->update(['target_rank' => $member->rank]);
            }
        }
    }

    /**
     * PIN lama yang belum terpakai. Peringkat tujuan dulu dipilih admin tapi
     * tidak pernah disimpan, jadi hanya bisa ditebak dari jumlah pin yang sudah
     * mereka bayar. Tebakan aman = jumlah pin yang BISA menjadi tujuan naik
     * peringkat (bukan Reseller, peringkat awal yang tidak sah sebagai tujuan):
     *   - 3 pin di harga lama = Agent (Reseller -> Agent, selisih 2 pin dibulatkan atas)
     *   - 5 pin = Distributor (Reseller -> Distributor, harga lama Rp 3.000.000)
     *   - 6 pin = Distributor
     * 1 pin dilewati karena hasilnya Reseller.
     *
     * PIN berpoin bukan paket ini (2 pin = Agent di aturan baru, tapi di aturan
     * lama 2 pin juga Agent) tetap cocok. Yang tidak cocok dibiarkan kosong —
     * lebih aman PIN jadi pendaftaran daripada salah menaikkan peringkat orang.
     */

    /**
     * Samakan `points` dan `price` dengan aturan baru untuk PIN naik peringkat yang
     * masih bisa dipakai. PIN yang sudah terpakai tidak diubah — riwayat transaksi
     * tidak boleh bergeser.
     *
     * PIN lama diterbitkan dengan `points = 0` dan `price = selisih paket`, karena
     * dulu tidak ada poin yang keluar. Supaya pelanggan tidak dirugikan, jumlah pin
     * diturunkan dari HARGA yang sudah mereka bayar (harga / harga per pin). Semua
     * harga lama kebetulan bulat: 600rb=1, 1,2jt=2, 1,8jt=3, 3jt=5, 3,6jt=6 pin.
     */
    private function selaraskanPoinDanHarga(): void
    {
        $hargaPerPin = (int) (config('sahih.registration_price_per_point') ?: 600000);

        if ($hargaPerPin < 1) {
            return;
        }

        $baris = DB::table('pins')
            ->where('type', 'registrasi_upgrade')
            ->whereNotNull('target_rank')
            ->where('status', 'issued')
            ->where('points', '<=', 0)
            ->get(['id', 'price']);

        foreach ($baris as $pin) {
            // Jumlah pin = harga yang sudah dibayar / harga per pin.
            $jumlahPin = intdiv((int) $pin->price, $hargaPerPin);

            if ($jumlahPin < 1) {
                $jumlahPin = 1;   // harga 0 atau tidak masuk akal: minimal 1 pin
            }

            DB::table('pins')->where('id', $pin->id)->update([
                'points' => $jumlahPin,
                'price' => $jumlahPin * $hargaPerPin,
            ]);
        }
    }

    public function down(): void
    {
        // Kembalikan nama jenis lama sebisanya. PIN gabungan tanpa target_rank
        // dianggap pendaftaran; yang punya target_rank dianggap upgrade.
        DB::table('pins')
            ->where('type', 'registrasi_upgrade')
            ->whereNull('target_rank')
            ->update(['type' => 'registration']);

        DB::table('pins')
            ->where('type', 'registrasi_upgrade')
            ->whereNotNull('target_rank')
            ->update(['type' => 'upgrade']);
    }
};
