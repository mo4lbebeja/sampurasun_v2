<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengadaan_id')->unique()->constrained('pengadaan')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->restrictOnDelete();
            $table->string('no_spm', 50)->nullable()->comment('Surat Perintah Membayar');
            $table->string('no_sp2d', 50)->nullable()->comment('Surat Perintah Pencairan Dana');
            $table->date('tanggal_bayar')->nullable();
            $table->enum('metode_bayar', ['transfer', 'cek', 'tunai', 'giro'])->default('transfer');
            $table->decimal('nilai_bayar', 18, 2)->default(0);
            $table->decimal('pajak_pph', 15, 2)->default(0);
            $table->decimal('pajak_ppn', 15, 2)->default(0);
            $table->decimal('nilai_bersih', 18, 2)->default(0);
            $table->string('bukti_bayar')->nullable();
            $table->enum('status', ['pending', 'diproses', 'lunas', 'batal'])->default('pending');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('tanggal_bayar');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
