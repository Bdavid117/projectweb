<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Thesis extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'modalidad', 'estado', 'titulo', 'director_id',
        'codirector_id', 'term_id', 'repositorio_url',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function codirector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'codirector_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }
}
