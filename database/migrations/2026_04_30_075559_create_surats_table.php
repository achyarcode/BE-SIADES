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
        Schema::create('surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nama_pemohon');
            $table->string('jenis_surat');
            $table->foreignId('jenis_surat_id')->nullable()->constrained('jenis_surats')->nullOnDelete();
            $table->text('keperluan');
            $table->text('keterangan')->nullable();
            $table->string('file_path')->nullable();
            $table->json('signature_position')->nullable();
            $table->string('status')->default('PENDING');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_penolakan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surats');
    }
};
