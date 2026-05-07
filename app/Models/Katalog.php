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
        'deskripsi',
        'harga',
        'satuan',
        'status',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
