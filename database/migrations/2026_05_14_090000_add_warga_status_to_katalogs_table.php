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
            $table->string('warga_status')->default('AKTIF')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('katalogs', function (Blueprint $table) {
            $table->dropColumn('warga_status');
        });
    }
};
