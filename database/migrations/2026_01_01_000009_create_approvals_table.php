<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_id')->constrained('usulan_pengadaan')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $table->enum('keputusan', ['disetujui', 'ditolak', 'revisi']);
            $table->text('catatan')->nullable()->comment('alasan/notes terutama saat ditolak atau minta revisi');
            $table->timestamp('tanggal_keputusan');
            $table->timestamps();

            $table->index(['usulan_id', 'tanggal_keputusan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
