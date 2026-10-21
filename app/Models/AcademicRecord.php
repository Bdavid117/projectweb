<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicRecord extends Model
{
    protected $fillable = ['student_id', 'term_id', 'papa', 'promedio_periodo', 'cupo_creditos', 'creditos_cursados', 'porcentaje_avance', 'numero_matricula', 'sincronizado_en'];

    protected $casts = ['sincronizado_en' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }
}
