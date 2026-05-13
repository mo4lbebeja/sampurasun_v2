<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dpa_anggaran', function (Blueprint $table) {
            $table->id();
            $table->year('tahun_anggaran');
            $table->string('no_dpa');
            $table->date('tanggal_dpa')->nullable();
            $table->string('nama_dpa')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tahun_anggaran', 'no_dpa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dpa_anggaran');
    }
};