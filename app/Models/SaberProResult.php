<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaberProResult extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'fecha_presentacion', 'puntaje_global', 'resultados_json'];

    protected $casts = [
        'fecha_presentacion' => 'date',
        'puntaje_global' => 'decimal:2',
        'resultados_json' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
