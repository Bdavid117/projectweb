<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidatePostSize as FrameworkValidatePostSize;

/**
 * Valida el tamano total de la peticion contra el limite configurado por la
 * aplicacion (soportes de hasta 5 MB por archivo y 5 archivos) en lugar de
 * depender del post_max_size del servidor, que puede ser mas permisivo.
 */
class ValidatePostSize extends FrameworkValidatePostSize
{
    protected function getPostMaxSize(): int
    {
        $kilobytes = (int) config('program.attachment_max_kb');
        $files = (int) config('program.attachment_max_files');

        $limit = $kilobytes * max(1, $files);

        return $limit > 0 ? $limit * 1024 : parent::getPostMaxSize();
    }
}
