<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['super-admin', 'sekretaris', 'bendahara', 'warga'];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $admin = User::firstOrCreate([
            'nik' => '1234567890123456', 
        ], [
            'no_kk' => '1234567890000000',
            'name' => 'Kepala Desa',
            'no_telp' => '081234567890',
            'password' => bcrypt('password123'), 
        ]);

        
        $admin->assignRole('super-admin');
    }
}