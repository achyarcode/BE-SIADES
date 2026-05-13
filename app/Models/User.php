<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles; 

    // 1. Fillable: buat mendaftarkan kolom apa saja yang BOLEH diisi dari form/request
    protected $fillable = [
        'nik', 
        'no_kk', 
        'name', 
        'username', 
        'no_telp', 
        'password'
    ];

    // 2. Hidden: Menyembunyikan kolom ini ketika data User dipanggil di API
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 3. Casts: Mengubah format data secara otomatis
    protected function casts(): array
    {
        return [
            'password' => 'hashed', // Otomatis meng-enkripsi/hash password saat disimpan
        ];
    }

    // Relasi: 1 User (Warga) bisa punya BANYAK Katalog
    public function katalogs()
    {
        return $this->hasMany(Katalog::class, 'user_id');
    }
}