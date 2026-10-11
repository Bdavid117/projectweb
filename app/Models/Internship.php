<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Internship extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'company_id', 'jefe_nombre', 'jefe_cargo', 'asesor_id',
        'fecha_inicio', 'fecha_fin', 'actividades', 'apreciacion_jefe',
        'apreciacion_jefe_fecha', 'apreciacion_asesor', 'apreciacion_asesor_fecha',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'apreciacion_jefe_fecha' => 'datetime',
        'apreciacion_asesor_fecha' => 'datetime',
    ];

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
