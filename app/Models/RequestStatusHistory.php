<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestStatusHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'request_status_history';

    protected $fillable = ['request_id', 'estado_anterior', 'estado_nuevo', 'user_id', 'fecha'];

    protected $casts = ['fecha' => 'datetime'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(StudentRequest::class, 'request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
