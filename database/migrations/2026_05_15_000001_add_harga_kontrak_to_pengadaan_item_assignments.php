<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama file: 2026_05_15_000001_add_harga_kontrak_to_pengadaan_item_assignments.php
 *
 * Tujuan: Menyimpan harga satuan kontrak aktual per item pengadaan.
 * Ini berbeda dari harga_satuan_estimasi (dari usulan) — ini hasil negosiasi.
 *
 * Default = 0 agar data lama tidak error.
 * Saat form kontrak dibuka pertama kali, Vue akan pre-fill dari harga_satuan_estimasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengadaan_item_assignments', function (Blueprint $table) {
            $table->decimal('harga_satuan_kontrak', 18, 2)
                ->default(0)
                ->after('usulan_item_id')
                ->comment('Harga satuan hasil negosiasi/kontrak aktual, berbeda dari estimasi');
        });
    }

    public function down(): void
    {
        Schema::table('pengadaan_item_assignments', function (Blueprint $table) {
            $table->dropColumn('harga_satuan_kontrak');
        });
    }
};