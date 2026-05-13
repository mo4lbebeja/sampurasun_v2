<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usulan_pengadaan', function (Blueprint $table) {
            $table->id();
            $table->string('no_usulan', 30)->unique()->comment('format: USL/{YYYY}/{MM}/{seq}');
            $table->foreignId('pemohon_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('anggaran_id')->constrained('anggaran')->restrictOnDelete();
            $table->date('tanggal_usulan');
            $table->string('judul', 200);
            $table->text('latar_belakang')->nullable();
            $table->text('keterangan')->nullable();
            $table->decimal('total_estimasi', 18, 2)->default(0);
            $table->enum('status', [
                'draft',
                'diajukan',
                'disetujui',
                'ditolak',
                'dalam_pengadaan',
                'dokumen',
                'pembayaran',
                'evaluasi',
                'selesai',
                'dibatalkan',
            ])->default('draft');
            $table->text('catatan_pemohon')->nullable();
            $table->string('file_pendukung')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'tanggal_usulan']);
            $table->index('pemohon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usulan_pengadaan');
    }
};
