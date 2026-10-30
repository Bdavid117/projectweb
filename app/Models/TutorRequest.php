<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Solicitud de un profesor para acompañar a un estudiante como tutor; el estudiante decide si comparte su información. */
class TutorRequest extends Model
{
    public const PENDING = 'pendiente';

    public const ACCEPTED = 'aceptada';

    public const REJECTED = 'rechazada';

    public const CANCELLED = 'cancelada';

    public const REVOKED = 'revocada';

    protected $fillable = ['student_id', 'tutor_id', 'estado', 'mensaje', 'respondida_en'];

    protected $casts = ['respondida_en' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function isPending(): bool
    {
        return $this->estado === self::PENDING;
    }
}
