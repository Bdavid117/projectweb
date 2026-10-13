<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = ['student_id', 'tipo', 'detalle', 'fecha_deteccion', 'atendida'];

    protected $casts = ['fecha_deteccion' => 'date', 'atendida' => 'boolean'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
