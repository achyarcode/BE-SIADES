<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat peran/jabatan
        // - super-admin: Kepala Desa (akses penuh)
        // - admin: Perangkat desa (akses menengah, gabungan sekretaris & bendahara)
        // - warga: Warga biasa
        $roles = ['super-admin', 'admin', 'warga'];
        $guards = ['web', 'sanctum'];
        
        foreach ($roles as $role) {
            foreach ($guards as $guard) {
                Role::firstOrCreate(['name' => $role, 'guard_name' => $guard]);
            }
        }

        // 2. Migrasi role lama → admin (jika masih ada di database)
        foreach (['sekretaris', 'bendahara'] as $oldRole) {
            if (!Role::where('name', $oldRole)->exists()) {
                continue;
            }
            foreach (User::role($oldRole)->get() as $user) {
                $user->removeRole($oldRole);
                if (!$user->hasRole('admin')) {
                    $user->assignRole('admin');
                }
            }
            Role::where('name', $oldRole)->delete();
        }

        // Hapus role "user" kalau ada (tidak pernah seharusnya ada)
        Role::where('name', 'user')->delete();

        // 3. Buat akun Kepala Desa (Super Admin)
        $admin = User::firstOrCreate(
            ['username' => 'kepaladesa'],
            [
                'nik' => '1234567890123456', 
                'no_kk' => '1234567890000000',
                'name' => 'Kepala Desa',
                'no_telp' => '081234567890',
                'password' => 'password123',
            ]
        );

        // 4. Tempelkan jabatan
        if (!$admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }

        // Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('Roles: super-admin, admin, warga');
        $this->command->info('Admin account: kepaladesa / password123');
    }
}