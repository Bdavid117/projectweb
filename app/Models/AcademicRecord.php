<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

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

    /**
     * El registro más reciente anterior al periodo, por estudiante (los periodos sin registro se saltan).
     *
     * @param  list<int>  $studentIds
     * @return Collection<int, self>
     */
    public static function previousFor(array $studentIds, AcademicTerm $term)
    {
        return static::whereIn('student_id', $studentIds)->with('term')
            ->whereHas('term', fn ($q) => $q->where('fecha_inicio', '<', $term->fecha_inicio))->get()
            ->groupBy('student_id')->map(fn ($group) => $group->sortByDesc(fn ($r) => $r->term->fecha_inicio)->first());
    }
}
