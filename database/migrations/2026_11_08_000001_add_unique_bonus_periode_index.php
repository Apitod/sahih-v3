<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Command bonus memakai pola "cek lalu tulis" pada available_at sebagai penanda
 * periode. Tanpa kunci di level DB, dua proses yang jalan bersamaan sama-sama
 * lolos cek → bonus dobel.
 *
 * MariaDB/MySQL tidak punya partial index, jadi kuncinya dibangun dari generated
 * column: hanya baris leadership/rm yang berisi nilai kunci, sisanya NULL (NULL
 * tidak ikut serta dalam unique index).
 */
return new class extends Migration
{
    public function up(): void
    {
        // MariaDB tidak mendukung index pada generated column bertipe VARCHAR tanpa
        // panjang — CHAR(7) cukup untuk 'leadership' dan 'rm'.
        DB::statement("ALTER TABLE bonus_entries
            ADD COLUMN periode_kunci CHAR(7)
            GENERATED ALWAYS AS (CASE WHEN kind IN ('leadership','rm') THEN kind END) VIRTUAL");

        Schema::table('bonus_entries', function (Blueprint $table) {
            $table->unique(['member_id', 'periode_kunci', 'available_at'], 'bonus_entries_periode_unik');
        });
    }

    public function down(): void
    {
        Schema::table('bonus_entries', function (Blueprint $table) {
            $table->dropUnique('bonus_entries_periode_unik');
            $table->dropColumn('periode_kunci');
        });
    }
};
