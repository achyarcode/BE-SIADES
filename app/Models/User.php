<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    // 1. Fillable: buat mendaftarkan kolom apa saja yang BOLEH diisi dari form/request
    protected $fillable = [
        'nik',
        'no_kk',
        'name',
        'username',
        'no_telp',
        'email',
        'rt',
        'rw',
        'alamat',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'password',
        'must_update_credentials',
        'credentials_updated_at',
        'profile_photo',
        'is_resident',
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
            'must_update_credentials' => 'boolean',
            'credentials_updated_at' => 'datetime',
            'is_resident' => 'boolean',
        ];
    }

    // Relasi: 1 User (Warga) bisa punya BANYAK Katalog
    public function katalogs()
    {
        return $this->hasMany(Katalog::class, 'user_id');
    }
}
