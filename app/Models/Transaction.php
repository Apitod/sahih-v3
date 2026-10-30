<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = ['code', 'type', 'member_id', 'pin_id', 'points', 'amount',
        'sponsor_bonus_total', 'posted_at', 'created_by'];

    protected $casts = ['posted_at' => 'datetime', 'points' => 'integer', 'amount' => 'integer',
        'sponsor_bonus_total' => 'integer'];

    public function member() { return $this->belongsTo(Member::class); }
    public function pin() { return $this->belongsTo(Pin::class); }
    public function bonuses() { return $this->hasMany(BonusEntry::class, 'source_transaction_id'); }
}
