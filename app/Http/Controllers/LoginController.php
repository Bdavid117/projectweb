<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $email = $request->input('email');
        $password = $request->input('password');

        $credentials = [
            'email' => is_string($email) ? Str::lower(trim($email)) : '',
            'password' => is_string($password) ? $password : '',
            'active' => true,
        ];

        if ($credentials['email'] === '' || $credentials['password'] === '' || ! Auth::attempt($credentials)) {
            return back()
                ->withInput(['email' => $credentials['email']])
                ->withErrors(['email' => 'Credenciales inválidas.']);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->homeUrl());
    }

    /**
     * Destino tras iniciar sesion. El destino por rol solo se usa si la ruta
     * correspondiente ya esta registrada: durante la construccion incremental
     * de los modulos algunas rutas todavia no existen.
     */
    private function homeUrl(): string
    {
        $route = Auth::user()->role->homeRoute();

        return Route::has($route) ? route($route) : route('login.show');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.show');
    }
}
