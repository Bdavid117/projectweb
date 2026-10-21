<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommitteeRequest extends Model
{
    use SoftDeletes;

    protected $table = 'requests';

    protected $fillable = ['student_id', 'request_type_id', 'term_id', 'descripcion', 'estado', 'fecha_radicacion', 'minute_id', 'decision', 'observaciones'];

    protected $casts = ['fecha_radicacion' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(RequestType::class, 'request_type_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'term_id');
    }

    public function minute(): BelongsTo
    {
        return $this->belongsTo(CommitteeMinute::class, 'minute_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class, 'request_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(RequestStatusHistory::class, 'request_id');
    }
}
