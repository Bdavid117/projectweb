<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    protected $fillable = ['student_id', 'experience_type_id', 'nombre', 'descripcion', 'fecha_inicio', 'fecha_fin', 'alcance', 'institucion', 'convenio', 'docente_id', 'rol', 'validada', 'validada_por', 'motivo_rechazo', 'rechazada_por'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date', 'validada' => 'boolean'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ExperienceType::class, 'experience_type_id');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function isRejected(): bool
    {
        return ! $this->validada && $this->motivo_rechazo !== null;
    }

    /** pendiente | validada | rechazada */
    public function estado(): string
    {
        return $this->validada ? 'validada' : ($this->isRejected() ? 'rechazada' : 'pendiente');
    }

    public function rechazador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rechazada_por');
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validada_por');
    }
}
