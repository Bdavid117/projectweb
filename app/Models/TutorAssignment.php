<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Vínculo vigente entre un profesor-tutor y un estudiante en un periodo; nace cuando el estudiante acepta una TutorRequest. */
class TutorAssignment extends Model
{
    protected $fillable = ['student_id', 'tutor_id', 'term_id'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }

    /** Solo los vínculos del periodo vigente: al cambiar de periodo hay que volver a solicitar. */
    public function scopeInCurrentTerm(Builder $query): Builder
    {
        return $query->where('tutor_assignments.term_id', AcademicTerm::current()?->id ?? 0);
    }

    public static function isLinked(User $tutor, Student $student): bool
    {
        return static::query()->inCurrentTerm()
            ->where('tutor_id', $tutor->id)
            ->where('student_id', $student->id)
            ->exists();
    }

    /** @return list<int> */
    public static function studentIdsOf(User $tutor): array
    {
        return static::query()->inCurrentTerm()->where('tutor_id', $tutor->id)->pluck('student_id')->all();
    }
}
