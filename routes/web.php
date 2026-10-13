<?php

use App\Http\Controllers\AdvisoryController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TutorshipController;
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

    Route::get('/mis-asesorias', [AdvisoryController::class, 'index'])->middleware('role:tutor')->name('advisories.index');

    Route::middleware('role:tutor,coordinacion')->group(function () {
        Route::get('/estudiantes/{student}/tutorias/crear', [TutorshipController::class, 'create'])->whereNumber('student')->name('tutorships.create');
        Route::post('/estudiantes/{student}/tutorias', [TutorshipController::class, 'store'])->whereNumber('student')->name('tutorships.store');
        Route::get('/tutorias/{tutoria}/editar', [TutorshipController::class, 'edit'])->whereNumber('tutoria')->name('tutorships.edit');
        Route::put('/tutorias/{tutoria}', [TutorshipController::class, 'update'])->whereNumber('tutoria')->name('tutorships.update');
        Route::delete('/tutorias/{tutoria}', [TutorshipController::class, 'destroy'])->whereNumber('tutoria')->name('tutorships.destroy');
    });

    Route::middleware('role:estudiante,coordinacion')->group(function () {
        Route::get('/solicitudes/nueva', [RequestController::class, 'create'])->name('requests.create');
        Route::post('/solicitudes', [RequestController::class, 'store'])->name('requests.store');
    });

    Route::get('/solicitudes', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/solicitudes/{solicitud}', [RequestController::class, 'show'])->whereNumber('solicitud')->name('requests.show');
    Route::get('/solicitudes/{solicitud}/soportes/{soporte}', [RequestController::class, 'download'])
        ->whereNumber(['solicitud', 'soporte'])
        ->name('requests.attachments.show');

    Route::middleware('role:coordinacion')->group(function () {
        Route::post('/solicitudes/{solicitud}/revision', [RequestController::class, 'startReview'])->whereNumber('solicitud')->name('requests.review');
        Route::post('/solicitudes/{solicitud}/decision', [RequestController::class, 'decide'])->whereNumber('solicitud')->name('requests.decision');
    });

    Route::get('/indicadores', [IndicatorController::class, 'index'])
        ->middleware('role:coordinacion')
        ->name('indicators.index');
});
