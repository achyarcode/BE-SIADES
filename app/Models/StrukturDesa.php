<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StrukturDesa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'jabatan',
        'rw',
        'rt',
        'alamat',
        'no_wa',
        'foto'
    ];
}
