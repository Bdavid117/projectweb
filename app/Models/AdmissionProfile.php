<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'school_id', 'puntaje_saber11', 'puntaje_admision', 'hobby_formal',
    ];

    protected $casts = [
        'puntaje_saber11' => 'decimal:2',
        'puntaje_admision' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
