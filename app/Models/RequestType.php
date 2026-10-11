<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestType extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion'];

    public function requests(): HasMany
    {
        return $this->hasMany(StudentRequest::class, 'request_type_id');
    }
}
