<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Katalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'kategori_katalog_id',
        'nama_produk',
        'deskripsi',
        'harga',
        'gambar',
        'kontak_wa',
        'status',
        'warga_status',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
    ];

    // Standardized status values (uppercase) for katalog entries
    public const STATUS_AKTIF = 'AKTIF';

    public const STATUS_NONAKTIF = 'NONAKTIF';

    public const STATUS_MENUNGGU = 'MENUNGGU';

    /**
     * Return allowed status values for validation.
     *
     * @return string[]
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_AKTIF,
            self::STATUS_NONAKTIF,
            self::STATUS_MENUNGGU,
        ];
    }

    /**
     * Status values allowed for warga self-toggle.
     *
     * @return string[]
     */
    public static function wargaStatuses(): array
    {
        return [
            self::STATUS_AKTIF,
            self::STATUS_NONAKTIF,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
