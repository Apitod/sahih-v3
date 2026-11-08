<?php

namespace App\Console\Commands;

use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\PointEntry;
use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan keadaan data produksi tanpa tinker (cPanel mematikan proc_open).
 * Dipakai untuk memastikan modul Royal Manager + bonus benar-benar hidup di prod.
 */
class DiagnosaSahih extends Command
{
    protected $signature = 'sahih:diagnosa';

    protected $description = 'Cetak ringkasan anggota, poin, bonus, pencairan, dan migrasi terakhir';

    public function handle(): int
    {
        $rm = DB::table('members')
            ->where('members.is_manager', true)
            ->whereNull('members.deleted_at')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('members as bawahan')
                    ->whereColumn('bawahan.sponsor_id', 'members.id')
                    ->where('bawahan.is_manager', true)
                    ->whereNull('bawahan.deleted_at');
            })->count();

        $this->line('== ANGGOTA ==');
        $this->line('total: '.Member::count());
        $this->line('manager: '.Member::where('is_manager', true)->count());
        $this->line('royal manager: '.$rm);
        foreach (DB::table('members')->select('status', DB::raw('count(*) as n'))->groupBy('status')->get() as $r) {
            $this->line("status {$r->status}: {$r->n}");
        }

        $this->line('== TRANSAKSI ==');
        $this->line('total: '.Transaction::count());

        $this->line('== POIN ==');
        foreach (PointEntry::select('kind', DB::raw('count(*) as n'), DB::raw('sum(delta) as p'))->groupBy('kind')->get() as $r) {
            $this->line("{$r->kind}: {$r->n} baris / {$r->p} poin");
        }

        $this->line('== BONUS ==');
        foreach (BonusEntry::select('kind', 'status', DB::raw('count(*) as n'), DB::raw('sum(amount) as total'))->groupBy('kind', 'status')->get() as $r) {
            $this->line("{$r->kind} {$r->status}: {$r->n} baris / Rp".number_format((float) $r->total, 0, ',', '.'));
        }

        $this->line('== PENCAIRAN ==');
        foreach (Withdrawal::select('status', DB::raw('count(*) as n'), DB::raw('sum(amount) as total'))->groupBy('status')->get() as $r) {
            $this->line("{$r->status}: {$r->n} / Rp".number_format((float) $r->total, 0, ',', '.'));
        }

        $this->line('== ATURAN AKTIF ==');
        $this->line('leadership per poin: Rp'.number_format((int) config('sahih.leadership_value_per_point'), 0, ',', '.'));
        $this->line('rm per poin: Rp'.number_format((int) config('sahih.royal_manager_value_per_point'), 0, ',', '.'));
        $this->line('pencairan aktif: '.(config('sahih.pencairan_aktif') ? 'ya' : 'tidak'));

        $this->line('== MIGRASI TERAKHIR ==');
        foreach (DB::table('migrations')->orderByDesc('id')->limit(5)->get() as $m) {
            $this->line($m->migration.' (batch '.$m->batch.')');
        }

        return self::SUCCESS;
    }
}
