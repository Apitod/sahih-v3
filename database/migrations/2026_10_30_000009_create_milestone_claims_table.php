<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestone_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->foreignId('milestone_reward_id')->constrained('milestone_rewards')->restrictOnDelete();
            $table->integer('cycle')->default(1);
            $table->enum('status', ['eligible', 'requested', 'handed', 'rejected'])->default('eligible');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('handed_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['member_id', 'milestone_reward_id', 'cycle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestone_claims');
    }
};
