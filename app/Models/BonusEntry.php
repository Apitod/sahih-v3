<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusEntry extends Model
{
    public $timestamps = false;

    protected $fillable = ['member_id', 'kind', 'amount', 'percent_bp', 'level',
        'source_transaction_id', 'status', 'available_at', 'withdrawal_id'];

    protected $casts = ['amount' => 'integer', 'available_at' => 'datetime', 'created_at' => 'datetime'];

    public function member() { return $this->belongsTo(Member::class); }
    public function transaction() { return $this->belongsTo(Transaction::class, 'source_transaction_id'); }

    /**
     * Asal bonus, buat ditampilkan ke anggota (transparansi).
     * Transaksi pembeli dipakai untuk sponsor & selisih; leadership/RM tidak punya
     * transaksi, jadi periodenya diambil dari `available_at`.
     */
    public function getSumberAttribute(): string
    {
        $trx = $this->transaction;

        if ($trx !== null) {
            $dari = $trx->member?->username ?? 'anggota';
            $label = 'Transaksi #' . $trx->id . ' · ' . $dari;

            return $this->kind === 'differential' && $this->level
                ? $label . ' · level ' . $this->level
                : $label;
        }

        return match ($this->kind) {
            'leadership' => 'Poin PL ' . ($this->available_at?->translatedFormat('F Y') ?? '—'),
            'rm' => 'Poin PL RM ' . ($this->available_at?->format('Y') ?? '—'),
            default => '—',
        };
    }

    /**
     * Bonus bisa ditarik di hari yang sama saat didapat (keputusan Owner, 8 Nov 2026,
     * mengikuti BUSINESS_RULES §3 poin 6: bonus cair harian). Tidak ada tenggang H+1.
     * Dikumpulkan di satu scope supaya aturan ini cuma punya satu tempat.
     */
    public function scopeBisaDitarik($query)
    {
        return $query->where('status', 'available');
    }
}
