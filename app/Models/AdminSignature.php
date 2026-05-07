<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSignature extends Model
{
    protected $fillable = [
        'admin_id',
        'signature_name',
        'file_path',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
