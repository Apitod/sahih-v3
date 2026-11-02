<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\RankHistory;
use App\Services\RankResolver;
use Illuminate\Console\Command;

/**
 * Menyelaraskan peringkat anggota lama dengan aturan PRD v3.0 §3.3.
 *
 * Sejak naik peringkat otomatis dari total PR, anggota yang PR-nya sudah
 * melewati ambang tapi peringkatnya belum naik (karena aturan lama) perlu
 * diselaraskan sekali. Perintah ini hanya MENAIKKAN, tidak pernah menurunkan.
 *
 * Default: hanya menampilkan (dry-run). Pakai --jalankan untuk menyimpan.
 */
class SelaraskanPeringkat extends Command
{
    protected $signature = 'sahih:selaraskan-peringkat {--jalankan : Simpan perubahan, bukan hanya menampilkan}';

    protected $description = 'Naikkan peringkat anggota lama yang PR-nya sudah memenuhi ambang (PRD v3.0 §3.3)';

    public function handle(RankResolver $ranks): int
    {
        $kering = ! $this->option('jalankan');
        $diubah = 0;

        $this->info($kering ? 'Mode tampil saja (tidak menyimpan).' : 'Mode JALANKAN — perubahan disimpan.');

        foreach (Member::where('rank', '!=', 'manager')->cursor() as $m) {
            $pr = $m->pointTotal('pr');
            $seharusnya = $m->rankForPr($pr);

            if ($ranks->weight($seharusnya) <= $ranks->weight($m->rank)) {
                continue;
            }

            $this->line("  {$m->username}: {$m->rank} -> {$seharusnya} (PR {$pr})");
            $diubah++;

            if ($kering) {
                continue;
            }

            $dari = $m->rank;
            $m->rank = $seharusnya;
            $m->save();

            RankHistory::create([
                'member_id' => $m->id,
                'from_rank' => $dari,
                'to_rank' => $seharusnya,
                'transaction_id' => null,
                'note' => "Selaras aturan PRD v3.0: total PR {$pr}",
            ]);
        }

        $this->newLine();
        $this->info($kering
            ? "{$diubah} anggota perlu diselaraskan. Jalankan dengan --jalankan untuk menyimpan."
            : "{$diubah} anggota sudah diselaraskan.");

        return self::SUCCESS;
    }
}
