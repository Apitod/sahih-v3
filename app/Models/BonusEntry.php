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
     * Bonus baru boleh ditarik H+1 setelah bonus didapat (aturan Owner, 8 Nov 2026).
     * Perbandingan per tanggal, bukan 24 jam bergulir: bonus tanggal 1 bisa ditarik
     * kapan saja tanggal 2, bukan baru pukul 23.00 kalau bonusnya masuk 23.00.
     * `available_at` kosong (bonus lama) jatuh ke `created_at`.
     */
    public function scopeBisaDitarik($query)
    {
        return $query->where('status', 'available')
            ->whereRaw('DATE(COALESCE(available_at, created_at)) <= ?', [today()->subDay()->toDateString()]);
    }
}
