<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSignature extends Model
{
    protected $fillable = [
        'admin_id',
        'signature_name',
        'file_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
