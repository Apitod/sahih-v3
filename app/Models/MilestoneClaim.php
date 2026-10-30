<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MilestoneClaim extends Model
{
    protected $fillable = ['member_id', 'milestone_reward_id', 'cycle', 'status',
        'requested_at', 'handed_at', 'handled_by', 'note'];
    protected $casts = ['requested_at' => 'datetime', 'handed_at' => 'datetime'];

    public function reward(): BelongsTo { return $this->belongsTo(MilestoneReward::class, 'milestone_reward_id'); }
    public function member(): BelongsTo { return $this->belongsTo(Member::class); }
}
