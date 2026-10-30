<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = ['user_id', 'codigo', 'documento', 'cohorte', 'estado', 'fecha_ingreso', 'telefono', 'direccion', 'ciudad'];

    protected $casts = ['fecha_ingreso' => 'date'];

    /**
     * Estudiantes cuya información puede ver el usuario: Coordinación todos, el estudiante a sí mismo y el
     * profesor solo a quienes aceptaron su solicitud de tutoría en el periodo vigente. Único punto de verdad
     * del alcance: listados, fichas, solicitudes y experiencias pasan por aquí.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return match ($user->role) {
            Role::Coordinacion => $query,
            Role::Profesor => $query->whereIn('students.id', TutorAssignment::query()->inCurrentTerm()
                ->where('tutor_id', $user->id)->select('student_id')),
            Role::Estudiante => $query->where('students.user_id', $user->id),
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admissionProfile(): HasOne
    {
        return $this->hasOne(AdmissionProfile::class);
    }

    public function healthProfile(): HasOne
    {
        return $this->hasOne(HealthProfile::class);
    }

    public function saberProResults(): HasMany
    {
        return $this->hasMany(SaberProResult::class);
    }

    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class);
    }

    public function tutorAssignments(): HasMany
    {
        return $this->hasMany(TutorAssignment::class);
    }

    public function theses(): HasMany
    {
        return $this->hasMany(Thesis::class);
    }

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(CommitteeRequest::class);
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    public function tutorships(): HasMany
    {
        return $this->hasMany(Tutorship::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }
}
