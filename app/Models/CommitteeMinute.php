<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommitteeMinute extends Model
{
    use HasFactory;

    protected $fillable = ['numero_acta', 'fecha', 'instancia', 'url'];

    protected $casts = ['fecha' => 'date'];

    public function requests(): HasMany
    {
        return $this->hasMany(StudentRequest::class, 'minute_id');
    }
}
