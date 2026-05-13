<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengadaan', function (Blueprint $table) {
            $table->foreignId('kpa_penandatangan_id')
                ->nullable()
                ->after('pejabat_penandatangan_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pengadaan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kpa_penandatangan_id');
        });
    }
};