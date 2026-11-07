<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = ['member_id', 'amount', 'bank_name', 'account_number',
        'account_holder', 'status', 'requested_at', 'processed_by', 'processed_at', 'note', 'proof_ref'];
    protected $casts = ['amount' => 'integer', 'processed_at' => 'datetime', 'requested_at' => 'datetime'];

    public function member() { return $this->belongsTo(Member::class); }
    public function processedBy() { return $this->belongsTo(\App\Models\Admin::class, 'processed_by'); }
}
