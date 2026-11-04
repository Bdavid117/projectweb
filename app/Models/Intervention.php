<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Intervention extends Model
{
    use SoftDeletes;

    public const ESTADO_ABIERTA = 'abierta';

    public const ESTADO_CERRADA = 'cerrada';

    /** Orígenes posibles (RF-36) → etiqueta legible. «observacion_profesor» no enlaza ningún registro. */
    public const ORIGENES = [
        'solicitud' => 'Solicitud',
        'alerta' => 'Alerta',
        'tutoria' => 'Tutoría',
        'observacion_profesor' => 'Observación de un profesor',
    ];

    /** Orígenes que sí enlazan un registro concreto (por `origen_id`). */
    private const ORIGEN_MODELS = [
        'solicitud' => CommitteeRequest::class,
        'alerta' => Alert::class,
        'tutoria' => Tutorship::class,
    ];

    protected $fillable = ['student_id', 'origen', 'origen_id', 'descripcion', 'responsable_id', 'fecha', 'resultado', 'estado', 'cerrada_en'];

    protected $casts = ['fecha' => 'date', 'cerrada_en' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ABIERTA);
    }

    public function isAbierta(): bool
    {
        return $this->estado === self::ESTADO_ABIERTA;
    }

    public function origenLabel(): string
    {
        return self::ORIGENES[$this->origen] ?? $this->origen;
    }

    /** Clase Eloquent del origen enlazable, o null si el origen no enlaza registros. */
    public static function modelForOrigen(?string $origen): ?string
    {
        return self::ORIGEN_MODELS[$origen] ?? null;
    }

    /** El registro de origen (solicitud, alerta o tutoría), o null si no hay enlace. */
    public function origenModel(): ?Model
    {
        $class = self::modelForOrigen($this->origen);

        return $class && $this->origen_id ? $class::find($this->origen_id) : null;
    }
}
