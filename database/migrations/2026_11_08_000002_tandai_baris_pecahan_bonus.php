<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci unik periode bonus (member_id, periode_kunci, available_at) dibuat untuk
 * mencegah command bonus menulis periode yang sama dua kali. Tapi penarikan
 * sebagian memecah satu baris bonus jadi dua (yang ditarik + sisanya), dan sisa
 * itu punya member/kind/available_at yang sama → unique index menolak → 500.
 *
 * Perbaikan: baris hasil pecahan ditandai lewat split_dari_id, dan kunci periode
 * hanya diisi untuk baris asli. Baris pecahan bernilai NULL sehingga tidak ikut
 * unique index (NULL tidak dihitung di MariaDB/MySQL).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonus_entries', function ($table) {
            $table->unsignedBigInteger('split_dari_id')->nullable()->after('withdrawal_id');
        });

        if (DB::getDriverName() !== 'mysql') {
            // sqlite tidak bisa MODIFY kolom → bangun ulang kolom generated + index-nya.
            // Ekspresi baru harus sama supaya invariannya ikut teruji di sqlite.
            Schema::table('bonus_entries', function ($table) {
                $table->dropUnique('bonus_entries_periode_unik');
                $table->dropColumn('periode_kunci');
            });

            DB::statement("ALTER TABLE bonus_entries
                ADD COLUMN periode_kunci VARCHAR(20)
                GENERATED ALWAYS AS (
                    CASE WHEN kind IN ('leadership','rm') AND split_dari_id IS NULL THEN kind END
                ) VIRTUAL");

            Schema::table('bonus_entries', function ($table) {
                $table->unique(['member_id', 'periode_kunci', 'available_at'], 'bonus_entries_periode_unik');
            });

            return;
        }

        DB::statement("ALTER TABLE bonus_entries
            MODIFY COLUMN periode_kunci VARCHAR(20)
            GENERATED ALWAYS AS (
                CASE WHEN kind IN ('leadership','rm') AND split_dari_id IS NULL THEN kind END
            ) VIRTUAL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bonus_entries
                MODIFY COLUMN periode_kunci VARCHAR(20)
                GENERATED ALWAYS AS (CASE WHEN kind IN ('leadership','rm') THEN kind END) VIRTUAL");
        }

        Schema::table('bonus_entries', function ($table) {
            $table->dropColumn('split_dari_id');
        });
    }
};
