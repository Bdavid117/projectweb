<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tutorship extends Model
{
    use SoftDeletes;

    protected $fillable = ['student_id', 'tutor_id', 'fecha', 'motivo', 'observaciones', 'compromisos'];

    protected $casts = ['fecha' => 'date'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
