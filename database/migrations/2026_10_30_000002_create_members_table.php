<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('username', 30)->unique();
            $table->string('name');
            $table->string('phone', 30);
            $table->string('address')->nullable();
            $table->string('password');
            // NULL = mendaftar tanpa kode referral (diizinkan, DECISIONS #46)
            $table->foreignId('sponsor_id')->nullable()->constrained('members')->restrictOnDelete();
            $table->enum('rank', ['reseller', 'agent', 'distributor', 'manager'])->default('reseller');
            $table->boolean('is_manager')->default(false);
            $table->timestamp('manager_since')->nullable();
            // cache dari ledger, bukan sumber kebenaran
            $table->integer('point_pa')->default(0);
            $table->integer('point_pr')->default(0);
            $table->integer('point_pl')->default(0);
            $table->enum('status', ['active', 'suspended'])->default('active');
            $table->timestamp('joined_at');
            $table->rememberToken();
            $table->timestamps();
            $table->index('rank');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
