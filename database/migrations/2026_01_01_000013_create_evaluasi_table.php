<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengadaan_id')->unique()->constrained('pengadaan')->cascadeOnDelete();
            $table->foreignId('evaluator_id')->constrained('users')->restrictOnDelete();
            $table->date('tanggal_evaluasi');
            $table->tinyInteger('nilai_kinerja_penyedia')->comment('skala 1-5');
            $table->tinyInteger('ketepatan_waktu')->comment('skala 1-5');
            $table->tinyInteger('kesesuaian_spesifikasi')->comment('skala 1-5');
            $table->tinyInteger('kualitas_barang')->comment('skala 1-5');
            $table->decimal('nilai_rata_rata', 4, 2)->nullable();
            $table->enum('rekomendasi', ['sangat_baik', 'baik', 'cukup', 'kurang', 'tidak_direkomendasikan']);
            $table->text('catatan_evaluasi')->nullable();
            $table->text('rekomendasi_perbaikan')->nullable();
            $table->string('file_laporan')->nullable();
            $table->timestamps();

            $table->index('tanggal_evaluasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi');
    }
};
