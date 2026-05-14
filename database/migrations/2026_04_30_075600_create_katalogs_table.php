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

            // Owner of the katalog entry
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Optional category reference
            $table->foreignId('kategori_katalog_id')->nullable()->constrained('kategori_katalogs')->nullOnDelete();

            // Product fields (match App\Models\Katalog->\$fillable)
            $table->string('nama_produk');
            $table->text('deskripsi')->nullable();
            $table->decimal('harga', 12, 2)->nullable();
            $table->string('gambar')->nullable();
            $table->string('kontak_wa')->nullable();

            // Status values use standardized uppercase labels
            $table->string('status')->default('MENUNGGU');

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
