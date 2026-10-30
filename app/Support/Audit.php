<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Audit
{
    /**
     * Registra en la bitácora los campos que cambiaron al actualizar $model con $changes;
     * no guarda nada si ningún valor es distinto.
     */
    public static function update(Request $request, Model $model, array $changes, string $accion): void
    {
        $model->fill($changes);
        $dirty = $model->getDirty();

        if ($dirty === []) {
            return;
        }

        $old = array_intersect_key($model->getOriginal(), $dirty);
        $model->save();

        self::log($request, $model, $accion, $old, $dirty);
    }

    /** Asienta un hecho que no es una actualización de campos (alta o baja de un registro). */
    public static function log(Request $request, Model $model, string $accion, array $old = [], array $new = []): void
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'accion' => $accion,
            'valores_anteriores' => $old,
            'valores_nuevos' => $new,
            'ip' => $request->ip(),
        ]);
    }
}
