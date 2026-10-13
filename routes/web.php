<?php

use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login.show');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/panel', [PanelController::class, 'index'])->name('panel.index');

    Route::middleware('role:profesor,tutor,coordinacion')->group(function () {
        Route::get('/estudiantes', [StudentController::class, 'index'])->name('students.index');
        Route::get('/estudiantes/{student}', [StudentController::class, 'show'])->whereNumber('student')->name('students.show');
    });

    Route::middleware('role:estudiante,coordinacion')->group(function () {
        Route::get('/solicitudes/nueva', [RequestController::class, 'create'])->name('requests.create');
        Route::post('/solicitudes/nueva', [RequestController::class, 'store'])->name('requests.store');
    });

    Route::get('/solicitudes', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/solicitudes/{solicitud}', [RequestController::class, 'show'])->whereNumber('solicitud')->name('requests.show');
    Route::get('/solicitudes/{solicitud}/soportes/{soporte}', [RequestController::class, 'download'])
        ->whereNumber(['solicitud', 'soporte'])
        ->name('requests.attachments.show');

    Route::get('/indicadores', [IndicatorController::class, 'index'])
        ->middleware('role:coordinacion')
        ->name('indicators.index');
});
