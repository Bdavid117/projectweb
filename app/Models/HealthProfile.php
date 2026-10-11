<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthProfile extends Model
{
    use HasFactory;

    /**
     * Datos sensibles: solo legibles por Coordinación (RNF-05) y con
     * auditoría de lectura. No exponer en serializaciones por defecto.
     */
    protected $fillable = [
        'student_id', 'resultado_oso', 'resultado_salud_mental',
        'resultado_salud_fisica', 'observaciones',
    ];

    protected $hidden = [
        'resultado_oso', 'resultado_salud_mental', 'resultado_salud_fisica',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
