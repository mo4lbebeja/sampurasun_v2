<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengadaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_id')->unique()->constrained('usulan_pengadaan')->cascadeOnDelete();
            $table->foreignId('pejabat_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('penyedia_id')->nullable()->constrained('penyedia')->nullOnDelete();
            $table->string('no_pengadaan', 50)->unique()->comment('format: PGD/{YYYY}/{MM}/{seq}');
            $table->enum('metode', [
                'pengadaan_langsung',
                'penunjukan_langsung',
                'tender',
                'tender_cepat',
                'e_purchasing',
                'swakelola',
            ])->default('pengadaan_langsung');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('no_kontrak', 100)->nullable();
            $table->date('tanggal_kontrak')->nullable();
            $table->decimal('nilai_kontrak', 18, 2)->default(0);
            $table->string('file_kontrak')->nullable();
            $table->string('file_hps')->nullable()->comment('Harga Perkiraan Sendiri');
            $table->enum('status', ['proses', 'kontrak', 'selesai', 'batal'])->default('proses');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengadaan');
    }
};
