<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PIN upgrade harus menyebut peringkat tujuannya.
 * Nominal dan poinnya diatur admin saat membuat PIN — bukan ditanam di kode.
 * Contoh yang diketahui: Reseller -> Distributor Rp 3.000.000,
 *                        Agent -> Distributor Rp 1.800.000.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pins', function (Blueprint $table) {
            $table->enum('target_rank', ['reseller', 'agent', 'distributor', 'manager'])
                ->nullable()
                ->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('pins', function (Blueprint $table) {
            $table->dropColumn('target_rank');
        });
    }
};
