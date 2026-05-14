<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Surat extends Model
{
    protected $fillable = [
        'user_id',
        'nama_pemohon',
        'jenis_surat',
        'jenis_surat_id',
        'keperluan',
        'keterangan',
        'file_path',
        'original_filename',
        'signature_position',
        'status',
        'approved_by',
        'alasan_penolakan',
    ];

    protected $casts = [
        'signature_position' => 'array',
    ];

    /**
     * Warga yang mengajukan surat.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Jenis surat (lookup table).
     */
    public function jenisSurat(): BelongsTo
    {
        return $this->belongsTo(JenisSurat::class);
    }

    /**
     * Admin/Kepala Desa yang menyetujui/menolak.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
