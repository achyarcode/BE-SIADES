<?php

namespace App\Models;

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
