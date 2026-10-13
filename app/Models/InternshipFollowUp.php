<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternshipFollowUp extends Model
{
    protected $fillable = ['internship_id', 'fecha', 'observacion', 'autor_id'];

    protected $casts = ['fecha' => 'date'];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(Internship::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
