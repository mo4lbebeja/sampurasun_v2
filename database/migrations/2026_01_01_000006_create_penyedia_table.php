<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyedia', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 200);
            $table->enum('jenis_badan', ['PT', 'CV', 'UD', 'Firma', 'Koperasi', 'Perorangan'])->default('CV');
            $table->string('npwp', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('nama_pic', 150)->nullable()->comment('Person In Charge');
            $table->string('rekening_bank', 50)->nullable();
            $table->string('nama_bank', 100)->nullable();
            $table->string('atas_nama_rekening', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyedia');
    }
};
