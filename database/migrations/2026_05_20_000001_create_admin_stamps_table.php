<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_stamps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('stamp_name');
            $table->string('file_path');
            $table->timestamps();

            $table->unique(['admin_id', 'stamp_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_stamps');
    }
};
