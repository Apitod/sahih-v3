<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Authenticatable
{
    protected $fillable = [
        'username', 'name', 'phone', 'address', 'password', 'sponsor_id',
        'rank', 'is_manager', 'manager_since', 'point_pa', 'point_pr', 'point_pl',
        'status', 'joined_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'is_manager' => 'boolean',
        'manager_since' => 'datetime',
        'joined_at' => 'datetime',
        'point_pa' => 'integer',
        'point_pr' => 'integer',
        'point_pl' => 'integer',
    ];

    public function sponsor(): BelongsTo { return $this->belongsTo(self::class, 'sponsor_id'); }
    public function downlines(): HasMany { return $this->hasMany(self::class, 'sponsor_id'); }
    public function pointEntries(): HasMany { return $this->hasMany(PointEntry::class); }
    public function bonusEntries(): HasMany { return $this->hasMany(BonusEntry::class); }
    public function milestoneClaims(): HasMany { return $this->hasMany(MilestoneClaim::class); }
    public function rankHistories(): HasMany { return $this->hasMany(RankHistory::class); }

    /** Total poin dari ledger — sumber kebenaran, bukan kolom cache. */
    public function pointTotal(string $kind): int
    {
        return (int) $this->pointEntries()->where('kind', $kind)->sum('delta');
    }

    /** Rantai upline dari sponsor langsung ke atas, berhenti kalau putus. */
    public function uplineRanks(int $max = 20): array
    {
        $ranks = [];
        $current = $this->sponsor;
        $guard = 0;

        while ($current !== null && $guard++ < $max) {
            $ranks[] = $current->rank;
            $current = $current->sponsor;
        }

        return $ranks;
    }
}
