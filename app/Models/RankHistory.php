<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankHistory extends Model
{
    public $timestamps = false;
    protected $fillable = ['member_id', 'from_rank', 'to_rank', 'transaction_id', 'note'];
    protected $casts = ['created_at' => 'datetime'];
}
