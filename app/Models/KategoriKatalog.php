<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriKatalog extends Model
{
    protected $fillable = [
        'nama',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Daftar katalog yang menggunakan kategori ini.
     */
    public function katalogs(): HasMany
    {
        return $this->hasMany(Katalog::class);
    }
}
