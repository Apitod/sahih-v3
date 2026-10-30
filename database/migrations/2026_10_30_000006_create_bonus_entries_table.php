<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bonus_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->enum('kind', ['sponsor', 'differential', 'leadership', 'rm']);
            $table->integer('amount');
            $table->integer('percent_bp')->nullable();
            $table->integer('level')->nullable();
            $table->foreignId('source_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->enum('status', ['pending', 'available', 'withdrawn', 'void'])->default('pending');
            $table->timestamp('available_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonus_entries');
    }
};
