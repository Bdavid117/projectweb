<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'term_id', 'papa', 'promedio_periodo', 'cupo_creditos',
        'creditos_cursados', 'porcentaje_avance', 'numero_matricula', 'sincronizado_en',
    ];

    protected $casts = [
        'papa' => 'decimal:2',
        'promedio_periodo' => 'decimal:2',
        'porcentaje_avance' => 'decimal:2',
        'sincronizado_en' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }
}
