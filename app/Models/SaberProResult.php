<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaberProResult extends Model
{
    protected $fillable = ['student_id', 'fecha_presentacion', 'puntaje_global', 'resultados_json'];

    protected $casts = ['fecha_presentacion' => 'date', 'resultados_json' => 'array'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
