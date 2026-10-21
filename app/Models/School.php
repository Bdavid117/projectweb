<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = ['nombre', 'caracter', 'municipio'];

    public function admissionProfiles(): HasMany
    {
        return $this->hasMany(AdmissionProfile::class);
    }
}
