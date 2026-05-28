<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $duplicates = DB::table('users')
            ->select('no_telp', DB::raw('COUNT(*) as total'))
            ->whereNotNull('no_telp')
            ->where('no_telp', '<>', '')
            ->groupBy('no_telp')
            ->having('total', '>', 1)
            ->pluck('no_telp')
            ->all();

        if (! empty($duplicates)) {
            throw new RuntimeException(
                'Tidak bisa membuat nomor HP unique karena masih ada nomor duplikat: '.implode(', ', $duplicates)
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('no_telp', 'users_no_telp_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_no_telp_unique');
        });
    }
};
