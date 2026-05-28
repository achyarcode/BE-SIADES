<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_resident')->default(false)->after('profile_photo');
        });

        DB::table('users')
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where(function ($query) {
                $query->where('roles.name', 'warga')
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNotNull('users.nik')
                            ->whereNotNull('users.no_kk')
                            ->where(function ($residentFields) {
                                $residentFields->whereNotNull('users.rt')
                                    ->orWhereNotNull('users.rw')
                                    ->orWhereNotNull('users.alamat');
                            });
                    });
            })
            ->update(['users.is_resident' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_resident');
        });
    }
};
