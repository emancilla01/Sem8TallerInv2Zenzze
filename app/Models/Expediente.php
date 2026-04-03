<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
    protected $fillable = [
        'nombre',
        'apellido',
        'fecha_llegada',
        'documento_path',
        'identificacion_path',
    ];

    protected $casts = [
        'fecha_llegada' => 'date',
    ];
}