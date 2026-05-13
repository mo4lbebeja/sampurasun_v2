<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('usulan_id')->nullable()->constrained('usulan_pengadaan')->cascadeOnDelete();
            $table->string('action', 100)->comment('contoh: usulan.submit, approval.approve, pengadaan.start');
            $table->string('subject_type', 100)->nullable()->comment('Eloquent model class');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 255);
            $table->json('properties')->nullable()->comment('payload before/after atau metadata');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['usulan_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
