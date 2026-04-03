<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expediente extends Model
{
    protected $fillable = [
        'nombre',
        'apellido',
        'fecha_llegada',
        'identificacion_path',
    ];

    protected $casts = [
        'fecha_llegada' => 'date',
    ];

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }
}