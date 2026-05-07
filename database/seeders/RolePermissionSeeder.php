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
        $roles = ['super-admin', 'sekretaris', 'bendahara', 'warga'];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // 2. Buat akun Kepala Desa (Super Admin)
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

        // 3. Tempelkan jabatan
        $admin->assignRole('super-admin');

        $this->command->info('Database roles ensured and Admin account verified: kepaladesa / password123');
    }
}