<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/panel';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // Ids numéricos acotados: un id fuera del rango de bigint debe dar 404, no un error de PostgreSQL.
        foreach (['student', 'solicitud', 'soporte', 'tutoria', 'experiencia'] as $parameter) {
            Route::pattern($parameter, '[0-9]{1,18}');
        }

        $this->routes(function () {
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
