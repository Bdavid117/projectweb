<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestType extends Model
{
    protected $fillable = ['nombre', 'descripcion'];

    public function requests(): HasMany
    {
        return $this->hasMany(CommitteeRequest::class, 'request_type_id');
    }
}
