<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicTerm extends Model
{
    protected $fillable = ['codigo', 'fecha_inicio', 'fecha_fin'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];

    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecord::class, 'term_id');
    }

    public static function current(): ?self
    {
        return static::query()->whereHas('academicRecords')->orderByDesc('fecha_inicio')->first()
            ?? static::query()->orderByDesc('fecha_inicio')->first();
    }
}
