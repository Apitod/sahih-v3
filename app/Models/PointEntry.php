<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointEntry extends Model
{
    public $timestamps = false;   // ledger: hanya created_at, tidak pernah diubah

    protected $fillable = ['member_id', 'kind', 'delta', 'source_type', 'source_id', 'note'];
    protected $casts = ['delta' => 'integer', 'created_at' => 'datetime'];

    public function member() { return $this->belongsTo(Member::class); }
}
