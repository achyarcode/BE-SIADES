<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Surat extends Model
{
    protected $fillable = [
        'user_id',
        'nama_pemohon',
        'jenis_surat',
        'keperluan',
        'file_path',
        'status',
        'signature_position',
    ];

    protected $casts = [
        'signature_position' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
