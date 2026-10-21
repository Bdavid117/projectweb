<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestAttachment extends Model
{
    protected $fillable = ['request_id', 'ruta', 'nombre_original', 'mime', 'tamano'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(CommitteeRequest::class, 'request_id');
    }
}
