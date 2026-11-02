<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\BonusEntry;
use App\Models\Member;
use App\Models\Pin;
use App\Models\PointEntry;
use App\Models\RankHistory;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Satu-satunya pintu masuk transaksi uang.
 *
 * Semua tulisan terjadi dalam satu database transaction: transaksi, PIN,
 * poin, bonus, peringkat, klaim, audit. Gagal satu = batal semua.
 */
class TransactionPoster
{
    public function __construct(
        private CommissionCalculator $commission,
        private RankResolver $ranks,
        private MilestoneEvaluator $milestones,
        private PackagePrice $packagePrice,
        private PengaturanHarga $harga,
    ) {}

    public function post(Member $buyer, int $pinId, string $type, ?int $adminId = null): Transaction
    {
        if (! in_array($type, ['join', 'upgrade', 'ro'], true)) {
            throw new \InvalidArgumentException("Jenis transaksi tidak dikenal: {$type}");
        }

        return DB::transaction(function () use ($buyer, $pinId, $type, $adminId) {
            $buyer = Member::whereKey($buyer->id)->lockForUpdate()->firstOrFail();

            if ($buyer->status !== 'active') {
                throw new \RuntimeException('Akun ini sedang dibekukan. Hubungi admin.');
            }

            // Kunci baris PIN: dua permintaan bersamaan tidak bisa memakai PIN yang sama.
            $pin = Pin::whereKey($pinId)->lockForUpdate()->firstOrFail();

            if (! $pin->isUsable()) {
                throw new \RuntimeException("PIN {$pin->code} tidak bisa dipakai (status: {$pin->status}).");
            }

            // Penjaga kepemilikan: PIN harus milik pembeli (kalau punya pemilik).
            if ($pin->assigned_member_id !== null && $pin->assigned_member_id !== $buyer->id) {
                throw new \RuntimeException("PIN {$pin->code} bukan untuk akun ini.");
            }

            // Penjaga jenis: jenis PIN harus cocok dengan jenis transaksi.
            $expectedTypes = [
                'registration' => 'join',
                'upgrade' => 'upgrade',
                'ro' => 'ro',
            ];
            if (($expectedTypes[$pin->type] ?? null) !== $type) {
                throw new \RuntimeException("Jenis PIN {$pin->type} tidak cocok untuk transaksi {$type}.");
            }

            // Penjaga harga RO (DECISIONS #56): harga per pin, diperiksa lagi saat
            // PIN dipakai. PIN yang terbit sebelum aturan ini, atau baris yang diubah
            // langsung di database, tidak boleh lolos dengan nominal lama.
            // Angka per pin diambil dari pengaturan supaya Owner bisa mengubahnya.
            if ($type === 'ro') {
                $expectedPrice = (int) $pin->points * $this->harga->ambil('harga_pin_ro');

                if ((int) $pin->price !== $expectedPrice) {
                    throw new \RuntimeException("Harga PIN {$pin->code} tidak sesuai untuk RO {$pin->points} poin.");
                }
            }

            // Penjaga PIN registrasi: poin harus salah satu paket §2, harga per pin × jumlah pin.
            if ($type === 'join') {
                $paket = array_map('intval', array_keys((array) config('sahih.rank_by_points')));

                if (! in_array((int) $pin->points, $paket, true)) {
                    throw new \RuntimeException("PIN {$pin->code} berisi {$pin->points} poin — bukan paket pendaftaran.");
                }

                $expectedPrice = (int) $pin->points * $this->harga->ambil('harga_pin_registrasi');

                if ((int) $pin->price !== $expectedPrice) {
                    throw new \RuntimeException("Harga PIN {$pin->code} tidak sesuai untuk pendaftaran {$pin->points} poin.");
                }
            }

            // Penjaga arah peringkat: upgrade tidak boleh ke peringkat sama atau lebih rendah (keputusan #55).
            if ($type === 'upgrade') {
                if ($pin->target_rank === null) {
                    throw new \RuntimeException("PIN {$pin->code} tidak punya peringkat tujuan — tidak bisa dipakai untuk upgrade.");
                }

                // Manager tidak dijual (DECISIONS #58): peringkat ini otomatis setelah Distributor + 20 poin PA.
                if ($pin->target_rank === 'manager') {
                    throw new \RuntimeException('Manager tidak bisa dibeli lewat PIN upgrade — Manager otomatis setelah Distributor + 20 poin PA.');
                }

                if ($this->ranks->weight($pin->target_rank) <= $this->ranks->weight($buyer->rank)) {
                    throw new \RuntimeException("PIN upgrade ke peringkat {$pin->target_rank} ditolak untuk akun yang sudah {$buyer->rank}.");
                }

                // Harga PIN upgrade = harga paket tujuan − harga paket sekarang (DECISIONS #59).
                // Diperiksa lagi di sini supaya PIN yang terbit sebelum peringkat pembeli
                // berubah tidak dipakai dengan harga lama.
                $expectedPrice = $this->packagePrice->upgradePrice($buyer->rank, $pin->target_rank);

                if ((int) $pin->price !== $expectedPrice) {
                    throw new \RuntimeException("Harga PIN {$pin->code} tidak sesuai untuk upgrade {$buyer->rank} ke {$pin->target_rank}.");
                }
            }

            // Satu anggota hanya boleh punya satu transaksi join. PIN registrasi kedua
            // akan menggandakan poin pribadi dan PA sponsor untuk orang yang sama.
            if ($type === 'join' && Transaction::where('member_id', $buyer->id)->where('type', 'join')->exists()) {
                throw new \RuntimeException('Akun ini sudah terdaftar. PIN registrasi hanya bisa dipakai sekali per anggota.');
            }

            $now = now();

            $trx = Transaction::create([
                'code' => 'TRX-'.Str::upper(Str::random(12)),
                'type' => $type,
                'member_id' => $buyer->id,
                'pin_id' => $pin->id,
                'points' => $pin->points,
                'amount' => $pin->price,
                'sponsor_bonus_total' => 0,
                'posted_at' => $now,
                'created_by' => $adminId,
            ]);

            $pin->update([
                'status' => 'used',
                'used_by_member_id' => $buyer->id,
                'used_at' => $now,
            ]);

            // Peringkat pembeli: saat mendaftar dari poin, saat upgrade dari target PIN.
            if ($type === 'join') {
                $this->applyRank($buyer, $this->ranks->forPoints($pin->points), $trx);
            }

            if ($type === 'upgrade') {
                if ($pin->target_rank === null) {
                    throw new \RuntimeException("PIN {$pin->code} tidak punya peringkat tujuan — tidak bisa dipakai untuk upgrade.");
                }
                $this->applyRank($buyer, $pin->target_rank, $trx);
            }

            // Poin pribadi pembeli (PRD v3.0 §3.5):
            //  - join & upgrade: PR saja
            //  - RO: PR + PA (koreksi Owner 2 Nov, DECISIONS #65)
            $kindsPembeli = $type === 'ro' ? ['pr', 'pa'] : ['pr'];
            $this->addPoints($buyer, $kindsPembeli, (int) $pin->points, $trx);

            // Naik peringkat otomatis dari total PR (PRD v3.0 §3.3).
            // Total PR termasuk PR yang datang dari anggota yang kita sponsori,
            // jadi pemeriksaan ini dijalankan untuk pembeli DAN sponsornya.
            $this->promoteByPr($buyer->fresh(), $trx);

            // Manager otomatis untuk pembeli (DECISIONS #49).
            // Dipanggil untuk SEMUA jenis transaksi, bukan hanya upgrade: sejak
            // PRD v3.0 RO juga memberi PA, jadi RO bisa memenuhi syarat Manager.
            $this->promoteIfQualified($buyer->fresh(), $trx);

            // Poin untuk sponsor langsung: join = PA + PR, RO/upgrade = PR saja.
            $sponsor = $buyer->sponsor;
            if ($sponsor !== null) {
                $kinds = $type === 'join' ? ['pa', 'pr'] : ['pr'];
                $this->addPoints($sponsor, $kinds, (int) $pin->points, $trx);

                // PR yang baru masuk bisa membuat sponsor naik peringkat.
                $this->promoteByPr($sponsor->fresh(), $trx);
                $this->promoteIfQualified($sponsor->fresh(), $trx);
            }

            // Bonus sponsor sepanjang rantai upline — hanya join & upgrade.
            // RO tidak membayar bonus sponsor (BUSINESS_RULES.md §6).
            $total = 0;
            if ($type !== 'ro') {
                $allocations = $this->commission->distribute((int) $pin->price, $buyer->uplineRanks());
                $cursor = $buyer->sponsor;

                foreach ($allocations as $allocation) {
                    if ($cursor === null) {
                        break;
                    }

                    if ($allocation['amount'] > 0) {
                        BonusEntry::create([
                            'member_id' => $cursor->id,
                            'kind' => 'sponsor',
                            'amount' => $allocation['amount'],
                            'percent_bp' => $allocation['percent_bp'],
                            'level' => $allocation['level'],
                            'source_transaction_id' => $trx->id,
                            // Bonus sponsor cair harian (BUSINESS_RULES.md §3 poin 5).
                            'status' => 'available',
                            'available_at' => $now,
                        ]);
                        $total += $allocation['amount'];
                    }

                    $cursor = $cursor->sponsor;
                }
            }

            $trx->update(['sponsor_bonus_total' => $total]);

            // Poin leadership (PRD v3.0 §3.8, DECISIONS #62): hanya Manager yang
            // menerima, dan Manager yang RO sendiri ikut menerima.
            $this->awardLeadership($buyer, (int) $pin->points, $trx, $type);

            // Hadiah milestone dinilai dari total PR (BUSINESS_RULES.md §8), dan PR
            // termasuk RO pribadi (§7). Pembeli menilai tangganya sendiri, sponsor
            // menilai poin yang baru masuk. Klaim idempoten lewat unique index, jadi
            // pemanggilan untuk orang yang sama tidak menghasilkan klaim dobel.
            $this->milestones->evaluate($buyer->fresh());

            if ($sponsor !== null) {
                $this->milestones->evaluate($sponsor->fresh());
            }

            AuditLog::create([
                'actor_type' => $adminId === null ? 'member' : 'admin',
                'actor_id' => $adminId ?? $buyer->id,
                'action' => 'transaction.posted',
                'subject_type' => Transaction::class,
                'subject_id' => $trx->id,
                'after' => [
                    'type' => $type,
                    'pin' => $pin->code,
                    'amount' => $pin->price,
                    'points' => $pin->points,
                    'bonus_total' => $total,
                ],
            ]);

            return $trx->fresh();
        });
    }

    /**
     * Poin leadership (PRD v3.0 §3.8, koreksi Owner 2 Nov).
     *
     * Aturan:
     *  - Hanya Manager yang menerima PL. Non-Manager TIDAK dicatat sama sekali
     *    (hangus, tidak ditabung) — dulu PL dicatat untuk siapa pun.
     *  - Manager yang melakukan RO sendiri juga menerima PL dari transaksinya.
     *  - PL mengalir ke upline sampai Manager pertama, lalu berhenti (kompresi #62).
     */
    private function awardLeadership(Member $buyer, int $points, Transaction $trx, string $type): void
    {
        if ($points <= 0) {
            return;
        }

        $maxLevel = (int) config('sahih.leadership_chain_max_level', 50);
        $sudahDapat = [];

        // Pembeli sendiri: hanya kalau dia Manager DAN transaksinya RO.
        if ($type === 'ro' && $buyer->is_manager) {
            $this->catatLeadership($buyer, $points, $trx, 'RO pribadi Manager');
            $sudahDapat[$buyer->id] = true;
        }

        // Kompresi (DECISIONS #62): begitu anggota di bawah sudah Manager, hak
        // leadership dari struktur itu berhenti. Jadi kalau pembelinya sendiri
        // Manager, upline tidak dapat apa-apa — tapi pembeli tetap dapat PL dari
        // RO pribadinya (koreksi Owner 2 Nov).
        if ($buyer->rank === 'manager') {
            return;
        }

        $cursor = $buyer->sponsor;
        $level = 0;

        while ($cursor !== null && $level < $maxLevel) {
            $level++;

            // Non-Manager dilewati: poinnya hangus, tidak dicatat (PRD v3.0 §3.8).
            // Dia juga tidak memutus rantai — rantai berhenti hanya di Manager.
            if ($cursor->is_manager && ! isset($sudahDapat[$cursor->id])) {
                $this->catatLeadership($cursor, $points, $trx, "level {$level}");
                $sudahDapat[$cursor->id] = true;

                break;   // Manager pertama: hak dari struktur ini berhenti di sini
            }

            $cursor = $cursor->sponsor;
        }
    }

    /** Satu baris poin leadership untuk seorang Manager. */
    private function catatLeadership(Member $member, int $points, Transaction $trx, string $asal): void
    {
        PointEntry::create([
            'member_id' => $member->id,
            'kind' => 'pl',
            'delta' => $points,
            'source_type' => 'transaction',
            'source_id' => $trx->id,
            'note' => "Poin leadership {$asal} dari transaksi {$trx->code}",
        ]);

        $member->point_pl = (int) $member->point_pl + $points;
        $member->save();
    }

    private function addPoints(Member $member, array $kinds, int $points, Transaction $trx): void
    {
        foreach ($kinds as $kind) {
            PointEntry::create([
                'member_id' => $member->id,
                'kind' => $kind,
                'delta' => $points,
                'source_type' => 'transaction',
                'source_id' => $trx->id,
                'note' => "Poin {$kind} dari transaksi {$trx->code}",
            ]);

            $column = "point_{$kind}";
            $member->{$column} = (int) $member->{$column} + $points;
        }

        $member->save();
    }

    private function applyRank(Member $member, string $rank, Transaction $trx): void
    {
        if ($this->ranks->weight($rank) <= $this->ranks->weight($member->rank)) {
            return;   // tidak ada penurunan peringkat
        }

        $from = $member->rank;
        $member->rank = $rank;
        $member->save();

        RankHistory::create([
            'member_id' => $member->id,
            'from_rank' => $from,
            'to_rank' => $rank,
            'transaction_id' => $trx->id,
            'note' => 'Peringkat dari poin pembelian',
        ]);
    }

    /**
     * Naik peringkat otomatis dari total PR (PRD v3.0 §3.3).
     *
     * Ambang: 1 PR = Reseller, 3 PR = Agent, 6 PR = Distributor. PR dihitung
     * dari ledger, termasuk PR yang datang dari anggota yang kita sponsori.
     * Manager tidak lewat jalur ini — syaratnya Distributor + 20 PA.
     */
    private function promoteByPr(Member $member, Transaction $trx): void
    {
        if ($member->rank === 'manager') {
            return;   // Manager tidak turun dan tidak naik lagi lewat PR
        }

        $pr = $member->pointTotal('pr');
        $seharusnya = $member->rankForPr($pr);

        if ($this->ranks->weight($seharusnya) <= $this->ranks->weight($member->rank)) {
            return;   // belum cukup, atau peringkat sekarang sudah lebih tinggi
        }

        $from = $member->rank;
        $member->rank = $seharusnya;
        $member->save();

        RankHistory::create([
            'member_id' => $member->id,
            'from_rank' => $from,
            'to_rank' => $seharusnya,
            'transaction_id' => $trx->id,
            'note' => "Otomatis: total PR mencapai {$pr}",
        ]);
    }

    /** Manager otomatis: sudah Distributor + 20 poin PA (DECISIONS #49). */
    private function promoteIfQualified(Member $member, Transaction $trx): void
    {
        if ($member->rank === 'manager') {
            if (! $member->is_manager || $member->manager_since === null) {
                $member->is_manager = true;
                $member->manager_since ??= now();
                $member->save();
            }

            return;
        }

        if ($member->is_manager || $member->rank !== 'distributor') {
            return;
        }

        if ($member->pointTotal('pa') < $this->harga->ambil('syarat_manager_pa')) {
            return;
        }

        $member->rank = 'manager';
        $member->is_manager = true;
        $member->manager_since = now();
        $member->save();

        RankHistory::create([
            'member_id' => $member->id,
            'from_rank' => 'distributor',
            'to_rank' => 'manager',
            'transaction_id' => $trx->id,
            'note' => 'Otomatis: PA mencapai syarat Manager',
        ]);
    }
}
