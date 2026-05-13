<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_upbj', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengadaan_id')->unique()->constrained('pengadaan')->cascadeOnDelete();
            $table->foreignId('petugas_id')->constrained('users')->restrictOnDelete();
            $table->string('no_bast', 100)->nullable()->comment('Berita Acara Serah Terima');
            $table->date('tanggal_bast')->nullable();
            $table->string('file_bast')->nullable();
            $table->string('file_invoice')->nullable();
            $table->string('file_faktur_pajak')->nullable();
            $table->string('file_kuitansi')->nullable();
            $table->string('file_spp')->nullable()->comment('Surat Permintaan Pembayaran');
            $table->json('file_lainnya')->nullable()->comment('array of file paths untuk dokumen tambahan');
            $table->boolean('is_complete')->default(false);
            $table->text('keterangan')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_upbj');
    }
};
