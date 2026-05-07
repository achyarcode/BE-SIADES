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
        Schema::table('katalogs', function (Blueprint $table) {
            if (!Schema::hasColumn('katalogs', 'user_id')) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('katalogs', 'nama_usaha')) {
                $table->string('nama_usaha');
            }
            if (!Schema::hasColumn('katalogs', 'kategori')) {
                $table->string('kategori');
            }
            if (!Schema::hasColumn('katalogs', 'deskripsi')) {
                $table->text('deskripsi')->nullable();
            }
            if (!Schema::hasColumn('katalogs', 'harga')) {
                $table->decimal('harga', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('katalogs', 'satuan')) {
                $table->string('satuan')->nullable();
            }
            if (!Schema::hasColumn('katalogs', 'status')) {
                $table->enum('status', ['Aktif', 'Nonaktif', 'Menunggu'])->default('Menunggu');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('katalogs', function (Blueprint $table) {
            $columns = ['user_id', 'nama_usaha', 'kategori', 'deskripsi', 'harga', 'satuan', 'status'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('katalogs', $col)) {
                    if ($col === 'user_id') {
                        $table->dropForeign(['user_id']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
