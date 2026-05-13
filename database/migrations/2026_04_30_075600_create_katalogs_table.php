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
        Schema::create('katalogs', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel users: Untuk melacak siapa pemilik produk ini
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); 
            
            $table->string('nama_produk');
            $table->text('deskripsi')->nullable();
            $table->integer('harga')->nullable(); // Boleh kosong jika harganya nego
            $table->string('gambar')->nullable(); // Menyimpan nama file foto produk
            $table->string('kontak_wa')->nullable(); // Nomor WA penjual untuk dihubungi pembeli
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('katalogs');
    }
};