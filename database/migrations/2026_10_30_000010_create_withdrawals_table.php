<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->integer('amount');
            $table->string('bank_name', 50);
            $table->string('account_number', 40);
            $table->string('account_holder');
            $table->enum('status', ['requested', 'approved', 'paid', 'rejected'])->default('requested');
            $table->timestamp('requested_at')->useCurrent();
            $table->foreignId('processed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->string('note')->nullable();
            $table->string('proof_ref')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
