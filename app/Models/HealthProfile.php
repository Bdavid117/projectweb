<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthProfile extends Model
{
    protected $fillable = ['student_id', 'resultado_oso', 'resultado_salud_mental', 'resultado_salud_fisica', 'observaciones'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
