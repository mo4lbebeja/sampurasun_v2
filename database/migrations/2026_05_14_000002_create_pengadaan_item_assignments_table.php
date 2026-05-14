<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama file: 2026_05_14_000002_create_pengadaan_item_assignments_table.php
 *
 * Tujuan:
 * Tabel pivot yang menghubungkan item-item dari usulan ke paket pengadaan tertentu.
 *
 * Aturan bisnis yang di-enforce di DB:
 * - Satu usulan_item hanya boleh masuk ke SATU paket pengadaan
 *   (UNIQUE KEY pada usulan_item_id)
 * - Jika paket dihapus, assignment-nya ikut terhapus (CASCADE)
 *
 * Jika ingin item bisa dibagi ke beberapa paket (misal 10 unit laptop:
 * 5 ke Paket 1, 5 ke Paket 2), hapus UNIQUE pada usulan_item_id dan
 * tambah kolom jumlah_dialokasikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengadaan_item_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengadaan_id')
                ->constrained('pengadaan')
                ->cascadeOnDelete()
                ->comment('Paket pengadaan yang mendapat item ini');

            $table->foreignId('usulan_item_id')
                ->constrained('usulan_items')
                ->cascadeOnDelete()
                ->comment('Item dari usulan yang di-assign ke paket ini');

            $table->timestamps();

            // Satu item hanya boleh masuk ke SATU paket
            $table->unique('usulan_item_id', 'pia_usulan_item_unique');

            // Index untuk query "semua item dalam satu paket"
            $table->index('pengadaan_id', 'pia_pengadaan_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengadaan_item_assignments');
    }
};