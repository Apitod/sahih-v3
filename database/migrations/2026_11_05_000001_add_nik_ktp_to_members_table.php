<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // NIK KTP: 16 digit angka, unik per anggota (DECISIONS #NIK)
            $table->string('nik_ktp', 16)->nullable()->unique()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('nik_ktp');
        });
    }
};
