<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommitteeMinute extends Model
{
    protected $fillable = ['numero_acta', 'fecha', 'instancia', 'url'];

    protected $casts = ['fecha' => 'date'];
}
