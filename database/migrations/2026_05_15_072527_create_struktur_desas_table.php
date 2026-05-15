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
    Schema::create('struktur_desas', function (Blueprint $table) {
        $table->id();
        $table->string('nama');
        $table->string('jabatan'); // Contoh: Kepala Desa, Sekretaris, Ketua RW
        $table->string('rw', 3)->nullable(); // Contoh: 001 (nullable karena Kades/Sekdes tidak punya nomor RW spesifik)
        $table->string('rt', 3)->nullable(); // Contoh: 002
        $table->text('alamat')->nullable();
        $table->string('no_wa')->nullable();
        $table->string('foto')->nullable(); // Untuk menyimpan path file foto
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('struktur_desas');
    }
};
