<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('belanja_langsung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggaran_id')->constrained('anggaran')->restrictOnDelete();
            $table->foreignId('pembelanja_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('dibayar_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->string('no_nota', 100)->nullable();
            $table->date('tanggal_belanja');
            $table->text('uraian');
            $table->enum('jenis', [
                'atk', 'konsumsi', 'transport', 'materai',
                'fotokopi', 'kebersihan', 'lainnya',
            ])->default('lainnya');
            $table->decimal('nominal', 15, 2);
            $table->string('file_nota')->nullable();

            $table->enum('status', [
                'draft', 'diajukan', 'disetujui', 'ditolak', 'dibayar',
            ])->default('draft')->index();

            $table->text('catatan')->nullable();
            $table->text('catatan_penolakan')->nullable();
            $table->date('tanggal_dibayar')->nullable();
            $table->integer('tahun_anggaran');

            $table->timestamps();

            $table->index(['tahun_anggaran', 'status']);
            $table->index('pembelanja_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('belanja_langsung');
    }
};