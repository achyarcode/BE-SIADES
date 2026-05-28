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
        if (Schema::hasTable('admin_stamps') && ! Schema::hasColumn('admin_stamps', 'is_active')) {
            Schema::table('admin_stamps', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('file_path');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('admin_stamps') && Schema::hasColumn('admin_stamps', 'is_active')) {
            Schema::table('admin_stamps', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
