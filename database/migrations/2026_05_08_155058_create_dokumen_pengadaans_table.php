<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_pengadaan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengadaan_id')
                ->constrained('pengadaan')
                ->cascadeOnDelete();

            $table->string('jenis');
            $table->string('nama_dokumen');
            $table->string('nomor')->unique();

            $table->string('kode_surat')->default('027');
            $table->unsignedInteger('nomor_urut');
            $table->string('kode_dokumen', 30);
            $table->string('bulan_romawi', 10);
            $table->year('tahun');

            $table->date('tanggal')->nullable();
            $table->text('keterangan')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['tahun', 'nomor_urut']);
            $table->unique(['pengadaan_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_pengadaan');
    }
};