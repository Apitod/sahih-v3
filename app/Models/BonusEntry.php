<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BonusEntry extends Model
{
    public $timestamps = false;

    protected $fillable = ['member_id', 'kind', 'amount', 'percent_bp', 'level',
        'source_transaction_id', 'status', 'available_at'];

    protected $casts = ['amount' => 'integer', 'available_at' => 'datetime', 'created_at' => 'datetime'];

    public function member() { return $this->belongsTo(Member::class); }
    public function transaction() { return $this->belongsTo(Transaction::class, 'source_transaction_id'); }
}
