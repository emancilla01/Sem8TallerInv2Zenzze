<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
    protected $fillable = [
        'nombre',
        'documento_path',
        'identificacion_path',
    ];
}