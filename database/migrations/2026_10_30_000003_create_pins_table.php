<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pins', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->enum('type', ['registration', 'upgrade', 'ro']);
            $table->integer('points');
            $table->integer('price');
            $table->enum('status', ['issued', 'used', 'void'])->default('issued');
            $table->foreignId('issued_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('assigned_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('used_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pins');
    }
};
