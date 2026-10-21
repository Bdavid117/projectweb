<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['nombre', 'nit', 'sector', 'tamano', 'ciudad'];

    public function internships(): HasMany
    {
        return $this->hasMany(Internship::class);
    }
}
