<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bonus_entries', function (Blueprint $table) {
            // Bonus yang dicairkan ditautkan ke penarikannya, supaya penolakan bisa mengembalikan saldo.
            $table->foreignId('withdrawal_id')->nullable()->constrained('withdrawals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bonus_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('withdrawal_id');
        });
    }
};
