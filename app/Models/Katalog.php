<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Katalog extends Model
{
    use HasFactory;

    // Mass Assignment Protection: Kolom yang dizinkan untuk diisi
    protected $fillable = [
        'user_id',
        'nama_produk',
        'deskripsi',
        'harga',
        'gambar',
        'kontak_wa',
    ];

    // Relasi: 1 Katalog (Produk) ini HANYA milik 1 User (Warga)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}