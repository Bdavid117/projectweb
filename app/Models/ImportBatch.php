<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['tipo', 'archivo', 'user_id', 'filas_ok', 'filas_error', 'log'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
