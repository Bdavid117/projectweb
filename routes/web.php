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
        Route::get('/estudiantes/{student}', [StudentController::class, 'show'])->name('students.show');
    });

    Route::get('/mis-asesorias', [AdvisoryController::class, 'index'])->middleware('role:tutor')->name('advisories.index');

    Route::get('/experiencias', [App\Http\Controllers\ExperienceController::class, 'index'])->middleware('role:estudiante,tutor,coordinacion')->name('experiencias.index');

    Route::middleware('role:estudiante,tutor,coordinacion')->group(function () {
        Route::get('/experiencias/crear', [App\Http\Controllers\ExperienceController::class, 'create'])->name('experiencias.create');
        Route::post('/experiencias', [App\Http\Controllers\ExperienceController::class, 'store'])->name('experiencias.store');
        Route::get('/experiencias/{id}/editar', [App\Http\Controllers\ExperienceController::class, 'edit'])->name('experiencias.edit');
        Route::put('/experiencias/{id}', [App\Http\Controllers\ExperienceController::class, 'update'])->name('experiencias.update');
        Route::delete('/experiencias/{id}', [App\Http\Controllers\ExperienceController::class, 'destroy'])->name('experiencias.destroy');
    });

    Route::middleware('role:tutor,coordinacion')->group(function () {
        Route::get('/estudiantes/{student}/tutorias/crear', [TutorshipController::class, 'create'])->name('tutorships.create');
        Route::post('/estudiantes/{student}/tutorias', [TutorshipController::class, 'store'])->name('tutorships.store');
        Route::get('/tutorias/{tutoria}/editar', [TutorshipController::class, 'edit'])->name('tutorships.edit');
        Route::put('/tutorias/{tutoria}', [TutorshipController::class, 'update'])->name('tutorships.update');
        Route::delete('/tutorias/{tutoria}', [TutorshipController::class, 'destroy'])->name('tutorships.destroy');
    });

    Route::middleware('role:estudiante,coordinacion')->group(function () {
        Route::get('/solicitudes/nueva', [RequestController::class, 'create'])->name('requests.create');
        Route::post('/solicitudes', [RequestController::class, 'store'])->name('requests.store');
    });

    Route::get('/solicitudes', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/solicitudes/{solicitud}', [RequestController::class, 'show'])->name('requests.show');
    Route::get('/solicitudes/{solicitud}/soportes/{soporte}', [RequestController::class, 'download'])
        
        ->name('requests.attachments.show');

    Route::middleware('role:coordinacion')->group(function () {
        Route::post('/solicitudes/{solicitud}/revision', [RequestController::class, 'startReview'])->name('requests.review');
        Route::post('/solicitudes/{solicitud}/decision', [RequestController::class, 'decide'])->name('requests.decision');
    });

    Route::get('/indicadores', [IndicatorController::class, 'index'])
        ->middleware('role:coordinacion')
        ->name('indicators.index');
});
