<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\PanelController;
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

    Route::get('/indicadores', [IndicatorController::class, 'index'])
        ->middleware('role:coordinacion')
        ->name('indicators.index');

    Route::middleware('role:tutor,coordinacion')->group(function () {
        Route::get('/estudiantes/{student}/tutorias/crear', [TutorshipController::class, 'create'])->name('tutorships.create');
        Route::post('/estudiantes/{student}/tutorias', [TutorshipController::class, 'store'])->name('tutorships.store');
        Route::get('/tutorias/{tutoria}/editar', [TutorshipController::class, 'edit'])->name('tutorships.edit');
        Route::put('/tutorias/{tutoria}', [TutorshipController::class, 'update'])->name('tutorships.update');
        Route::delete('/tutorias/{tutoria}', [TutorshipController::class, 'destroy'])->name('tutorships.destroy');
    });
});
