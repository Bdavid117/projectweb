<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                return redirect($this->homeUrl());
            }
        }

        return $next($request);
    }

    /**
     * Destino de un usuario ya autenticado. Se prefiere la ruta de inicio del
     * rol; si todavia no esta registrada se cae al home de la aplicacion.
     */
    private function homeUrl(): string
    {
        $user = Auth::user();

        if ($user) {
            $route = $user->role->homeRoute();

            if (Route::has($route)) {
                return route($route);
            }
        }

        return RouteServiceProvider::HOME;
    }
}
