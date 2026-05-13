<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anggaran', function (Blueprint $table) {
            $table->id();
            $table->year('tahun');
            $table->string('kode_rekening', 50);
            $table->string('nama_rekening', 200);
            $table->text('uraian')->nullable();
            $table->decimal('pagu', 18, 2)->default(0);
            $table->decimal('terpakai', 18, 2)->default(0);
            $table->decimal('sisa', 18, 2)->storedAs('pagu - terpakai');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tahun', 'kode_rekening']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anggaran');
    }
};
