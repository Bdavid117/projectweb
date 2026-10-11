<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperienceType extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'categoria'];

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class, 'experience_type_id');
    }
}
