<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['actor_type', 'actor_id', 'action', 'subject_type',
        'subject_id', 'before', 'after', 'ip'];
    protected $casts = ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime'];
}
