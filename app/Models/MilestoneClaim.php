<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilestoneClaim extends Model
{
    protected $fillable = ['member_id', 'milestone_reward_id', 'cycle', 'status',
        'requested_at', 'handed_at', 'handled_by', 'note'];
    protected $casts = ['requested_at' => 'datetime', 'handed_at' => 'datetime'];
}
