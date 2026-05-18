<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('pengadaan_item_assignments', function (Blueprint $table) {
        // 1. Drop foreign key yang pakai index itu dulu
        $table->dropForeign(['usulan_item_id']);

        // 2. Sekarang baru bisa drop unique index-nya
        $table->dropUnique('pia_usulan_item_unique');

        // 3. Buat index biasa untuk FK (MySQL butuh index di kolom FK)
        $table->index('usulan_item_id', 'pia_usulan_item_idx');

        // 4. Buat ulang foreign key menggunakan index biasa
        $table->foreign('usulan_item_id', 'pia_usulan_item_fk')
              ->references('id')
              ->on('usulan_items')
              ->onDelete('cascade');

        // 5. Unique constraint baru — kombinasi pengadaan + item
        $table->unique(['pengadaan_id', 'usulan_item_id'], 'pia_pengadaan_item_unique');
    });
}

public function down(): void
{
    Schema::table('pengadaan_item_assignments', function (Blueprint $table) {
        $table->dropForeign('pia_usulan_item_fk');
        $table->dropIndex('pia_usulan_item_idx');
        $table->dropUnique('pia_pengadaan_item_unique');

        $table->unique(['usulan_item_id'], 'pia_usulan_item_unique');
        $table->foreign('usulan_item_id')
              ->references('id')
              ->on('usulan_items')
              ->onDelete('cascade');
    });
}

};
