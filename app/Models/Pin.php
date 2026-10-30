<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pin extends Model
{
    protected $fillable = ['code', 'type', 'target_rank', 'points', 'price', 'status', 'issued_by',
        'assigned_member_id', 'used_by_member_id', 'used_at'];

    protected $casts = ['used_at' => 'datetime', 'points' => 'integer', 'price' => 'integer'];

    public function isUsable(): bool { return $this->status === 'issued'; }
}
