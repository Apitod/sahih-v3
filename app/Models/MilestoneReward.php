<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilestoneReward extends Model
{
    protected $fillable = ['threshold', 'name', 'value_internal', 'is_active', 'sort'];
    protected $casts = ['is_active' => 'boolean', 'threshold' => 'integer'];
    // value_internal tidak boleh pernah ikut terkirim ke tampilan anggota
    protected $hidden = ['value_internal'];
}
