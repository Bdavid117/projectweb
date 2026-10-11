<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['fuente', 'term_id', 'estado', 'mensaje_error', 'registros', 'ejecutado_en'];

    protected $casts = ['ejecutado_en' => 'datetime'];

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }
}
