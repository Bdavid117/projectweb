<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicTerm extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'starts_on', 'ends_on', 'active'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'active' => 'boolean',
    ];

    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class, 'term_id');
    }

    public function tutorAssignments(): HasMany
    {
        return $this->hasMany(TutorAssignment::class, 'term_id');
    }
}
