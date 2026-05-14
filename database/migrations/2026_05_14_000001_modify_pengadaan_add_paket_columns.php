<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nama file: 2026_05_14_000001_modify_pengadaan_add_paket_columns.php
 *
 * Fix error: SQLSTATE[HY000] 1553 - Cannot drop index needed in a foreign key constraint
 *
 * Urutan yang benar di MySQL:
 * 1. Drop foreign key constraint dulu  → constraint-nya hilang
 * 2. Drop unique index                 → index bebas dihapus
 * 3. Buat regular index (non-unique)   → FK butuh index, tapi tidak harus unique
 * 4. Pasang kembali foreign key        → constraint aktif lagi
 * 5. Tambah kolom baru nama_paket dan estimasi_paket
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengadaan', function (Blueprint $table) {

            // ── Langkah 1: Drop foreign key constraint dulu ──────────
            // Nama constraint: pengadaan_usulan_id_foreign
            // (terlihat di SQL dump pada baris CONSTRAINT `pengadaan_usulan_id_foreign`)
            $table->dropForeign('pengadaan_usulan_id_foreign');

            // ── Langkah 2: Baru drop unique index ────────────────────
            // Sekarang aman karena FK sudah tidak mengandalkan index ini
            $table->dropUnique('pengadaan_usulan_id_unique');

            // ── Langkah 3: Buat regular index (non-unique) ───────────
            // FK butuh index pada kolom referensi, tapi tidak harus UNIQUE.
            // Dengan ini satu usulan bisa punya banyak paket pengadaan.
            $table->index('usulan_id', 'pengadaan_usulan_id_index');

            // ── Langkah 4: Pasang kembali foreign key ────────────────
            $table->foreign('usulan_id', 'pengadaan_usulan_id_foreign')
                ->references('id')
                ->on('usulan_pengadaan')
                ->cascadeOnDelete();

            // ── Langkah 5: Tambah kolom baru ─────────────────────────
            $table->string('nama_paket', 200)
                ->nullable()
                ->after('usulan_id')
                ->comment('Nama paket jika satu usulan dipecah jadi beberapa paket');

            $table->decimal('estimasi_paket', 18, 2)
                ->default(0)
                ->after('nama_paket')
                ->comment('Porsi estimasi dari total_estimasi usulan untuk paket ini');
        });
    }

    public function down(): void
    {
        Schema::table('pengadaan', function (Blueprint $table) {
            // Hapus kolom baru
            $table->dropColumn(['nama_paket', 'estimasi_paket']);

            // Balikkan: drop FK → drop regular index → buat unique → pasang FK kembali
            $table->dropForeign('pengadaan_usulan_id_foreign');
            $table->dropIndex('pengadaan_usulan_id_index');
            $table->unique('usulan_id', 'pengadaan_usulan_id_unique');
            $table->foreign('usulan_id', 'pengadaan_usulan_id_foreign')
                ->references('id')
                ->on('usulan_pengadaan')
                ->cascadeOnDelete();
        });
    }
};