<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sub_kegiatan')->nullable();
            $table->string('nama_kegiatan');
            $table->year('tahun_anggaran');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tahun_anggaran', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_kegiatan');
    }
};