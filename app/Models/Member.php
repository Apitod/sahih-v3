<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Authenticatable
{
    use SoftDeletes;

    protected $fillable = [
        'username', 'name', 'phone', 'address', 'nik_ktp', 'password', 'sponsor_id',
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
    public function uplineRanks(int $max = 0): array
    {
        // Batas diambil dari config (DECISIONS #60: 50 tingkat). Parameter $max
        // masih diterima supaya pemanggil lama tidak pecah.
        $max = $max > 0 ? $max : (int) config('sahih.sponsor_chain_max_level', 50);

        $ranks = [];
        $current = $this->sponsor;
        $guard = 0;

        while ($current !== null && $guard++ < $max) {
            $ranks[] = $current->rank;
            $current = $current->sponsor;
        }

        return $ranks;
    }

    /**
     * Peringkat yang seharusnya dimiliki anggota berdasarkan total PR
     * (PRD v3.0 §3.3): 1 PR = Reseller, 3 PR = Agent, 6 PR = Distributor.
     * Manager tidak ditentukan dari PR — syaratnya Distributor + 20 PA.
     */
    public function rankForPr(int $pr): string
    {
        $peta = (array) config('sahih.rank_by_pr', []);
        $dapat = 'reseller';

        foreach ($peta as $rank => $ambang) {
            if ($pr >= (int) $ambang) {
                $dapat = $rank;
            }
        }

        return $dapat;
    }
}
