<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PIN — kode sekali pakai dari admin.
 *
 * Sejak DECISIONS #73 (Owner 2 Nov) jenisnya tinggal DUA:
 *  - `registrasi_upgrade` — satu PIN yang bisa dipakai untuk mendaftarkan anggota
 *    baru ATAU menaikkan peringkat anggota lama.
 *  - `ro`                 — repeat order.
 *
 * Karena satu jenis menampung dua jalur, yang menentukan jalur bukan lagi nama
 * jenis melainkan `target_rank`:
 *  - `target_rank` kosong  → PIN pendaftaran, transaksi `join`
 *  - `target_rank` terisi  → PIN naik peringkat, transaksi `upgrade`
 */
class Pin extends Model
{
    public const JENIS_REGISTRASI_UPGRADE = 'registrasi_upgrade';

    public const JENIS_RO = 'ro';

    /** Dua jenis PIN yang sah. Dipakai untuk validasi dan tampilan. */
    public const JENIS = [self::JENIS_REGISTRASI_UPGRADE, self::JENIS_RO];


    protected $fillable = ['code', 'type', 'target_rank', 'points', 'price', 'status', 'issued_by',
        'assigned_member_id', 'used_by_member_id', 'used_at'];

    protected $casts = ['used_at' => 'datetime', 'points' => 'integer', 'price' => 'integer'];

    /** Daftar jenis PIN yang sah — dua, bukan tiga. */
    public static function jenis(): array
    {
        return self::JENIS;
    }

    /** Jenis PIN, sudah dinormalkan dari nama lama kalau perlu. */
    public function jenisPin(): string
    {
        return (string) $this->type;
    }

    /** Apakah jenisnya salah satu dari dua yang sah? */
    public function jenisSah(): bool
    {
        return in_array($this->jenisPin(), self::JENIS, true);
    }

    public function isRegistrasiUpgrade(): bool
    {
        return $this->jenisPin() === self::JENIS_REGISTRASI_UPGRADE;
    }

    public function isRo(): bool
    {
        return $this->jenisPin() === self::JENIS_RO;
    }

    /** PIN gabungan tanpa peringkat tujuan = dipakai untuk mendaftarkan anggota baru. */
    public function untukPendaftaran(): bool
    {
        return $this->isRegistrasiUpgrade() && $this->target_rank === null;
    }

    /** PIN gabungan dengan peringkat tujuan = dipakai untuk menaikkan peringkat. */
    public function untukUpgrade(): bool
    {
        return $this->isRegistrasiUpgrade() && $this->target_rank !== null;
    }

    /**
     * Peringkat yang dituju, dihitung dari jumlah pin.
     *
     * 1 pin = Reseller, 3 pin = Agent, 6 pin = Distributor (config sahih.rank_by_points).
     * Mengembalikan null kalau jumlah pinnya bukan paket yang sah.
     */
    public function targetRankDariJumlahPin(): ?string
    {
        $peta = (array) config('sahih.rank_by_points', []);

        return $peta[(int) $this->points] ?? null;
    }

    public function isUsable(): bool
    {
        return $this->status === 'issued';
    }

    public function assignedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'assigned_member_id');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'used_by_member_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'issued_by');
    }
}
