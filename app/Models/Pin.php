<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pin extends Model
{
    protected $fillable = ['code', 'type', 'target_rank', 'points', 'price', 'status', 'issued_by',
        'assigned_member_id', 'used_by_member_id', 'used_at'];

    protected $casts = ['used_at' => 'datetime', 'points' => 'integer', 'price' => 'integer'];

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
