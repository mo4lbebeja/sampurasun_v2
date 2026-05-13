<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usulan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_id')->constrained('usulan_pengadaan')->cascadeOnDelete();
            $table->foreignId('kategori_id')->constrained('kategori_barang')->restrictOnDelete();
            $table->string('nama_barang', 200);
            $table->text('spesifikasi')->nullable();
            $table->integer('jumlah')->default(1);
            $table->string('satuan', 30)->default('unit');
            $table->decimal('harga_satuan_estimasi', 15, 2)->default(0);
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('usulan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usulan_items');
    }
};
