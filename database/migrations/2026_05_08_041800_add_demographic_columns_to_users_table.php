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
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('password');
            $table->string('rt', 10)->nullable()->after('no_telp');
            $table->string('rw', 10)->nullable()->after('rt');
            $table->text('alamat')->nullable()->after('rw');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable()->after('alamat');
            $table->string('tempat_lahir')->nullable()->after('jenis_kelamin');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email', 'rt', 'rw', 'alamat', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir']);
        });
    }
};
