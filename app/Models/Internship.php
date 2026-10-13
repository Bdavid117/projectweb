<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Internship extends Model
{
    protected $fillable = ['student_id', 'company_id', 'jefe_nombre', 'jefe_cargo', 'asesor_id', 'fecha_inicio', 'fecha_fin', 'actividades', 'apreciacion_jefe', 'apreciacion_asesor'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(InternshipFollowUp::class);
    }
}
