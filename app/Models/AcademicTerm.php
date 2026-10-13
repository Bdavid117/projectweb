<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicTerm extends Model
{
    protected $fillable = ['codigo', 'fecha_inicio', 'fecha_fin'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
}
