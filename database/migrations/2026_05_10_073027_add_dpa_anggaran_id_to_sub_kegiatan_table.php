<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_kegiatan', function (Blueprint $table) {
            $table->foreignId('dpa_anggaran_id')
                ->nullable()
                ->after('id')
                ->constrained('dpa_anggaran')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sub_kegiatan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dpa_anggaran_id');
        });
    }
};