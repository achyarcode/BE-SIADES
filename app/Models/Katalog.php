<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Katalog extends Model
{
    protected $fillable = [
        'user_id',
        'nama_usaha',
        'kategori',
        'kategori_katalog_id',
        'deskripsi',
        'harga',
        'satuan',
        'status',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
    ];

    /**
     * Pemilik katalog.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kategori katalog (lookup table).
     */
    public function kategoriKatalog(): BelongsTo
    {
        return $this->belongsTo(KategoriKatalog::class);
    }
}
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
