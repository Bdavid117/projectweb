<?php

namespace App\Support;

use Illuminate\Http\Request;

class Input
{
    /** Valor de texto recortado; vacío si falta o llega como arreglo (`q[]=x`). */
    public static function text(Request $request, string $key): string
    {
        $value = $request->query($key, $request->input($key));

        return is_string($value) ? trim($value) : '';
    }

    /** Entero positivo; 0 si falta, no es numérico o llega como arreglo. */
    public static function id(Request $request, string $key): int
    {
        $value = self::text($request, $key);

        return ctype_digit($value) ? (int) $value : 0;
    }
}
