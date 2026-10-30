<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->enum('type', ['join', 'upgrade', 'ro']);
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            // satu PIN = satu transaksi. Kunci anti-dobel.
            $table->foreignId('pin_id')->unique()->constrained('pins')->restrictOnDelete();
            $table->integer('points');
            $table->integer('amount');
            $table->integer('sponsor_bonus_total')->default(0);
            $table->timestamp('posted_at');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
