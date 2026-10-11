<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['request_id', 'ruta', 'nombre_original', 'mime', 'tamano'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(StudentRequest::class, 'request_id');
    }
}
