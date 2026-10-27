<?php

use App\Http\Controllers\AcademicHistoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdvisoryController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\IndicatorController;
use App\Http\Controllers\InternshipController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ThesisController;
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

Route::redirect('/', '/panel');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login.show');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/notificaciones', NotificationController::class)->name('notifications.index');

    Route::get('/panel', [PanelController::class, 'index'])->name('panel.index');

    Route::middleware('role:profesor,tutor,coordinacion')->group(function () {
        Route::get('/estudiantes', [StudentController::class, 'index'])->name('students.index');
        Route::get('/estudiantes/{student}', [StudentController::class, 'show'])->name('students.show');
    });

    Route::get('/mis-asesorias', [AdvisoryController::class, 'index'])->middleware('role:tutor')->name('advisories.index');

    Route::get('/experiencias', [ExperienceController::class, 'index'])->name('experiencias.index');

    Route::middleware('role:estudiante,coordinacion')->group(function () {
        Route::get('/experiencias/crear', [ExperienceController::class, 'create'])->name('experiencias.create');
        Route::post('/experiencias', [ExperienceController::class, 'store'])->name('experiencias.store');
        Route::get('/experiencias/{experiencia}/editar', [ExperienceController::class, 'edit'])->name('experiencias.edit');
        Route::put('/experiencias/{experiencia}', [ExperienceController::class, 'update'])->name('experiencias.update');
        Route::delete('/experiencias/{experiencia}', [ExperienceController::class, 'destroy'])->name('experiencias.destroy');
        Route::post('/experiencias/{experiencia}/validacion', [ExperienceController::class, 'validateExperience'])->name('experiencias.validate');
        Route::post('/experiencias/{experiencia}/rechazo', [ExperienceController::class, 'reject'])->name('experiencias.reject');
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

    Route::get('/perfil', [ProfileController::class, 'show'])->middleware('role:estudiante')->name('profile.show');
    Route::put('/perfil', [ProfileController::class, 'update'])->middleware('role:estudiante')->name('profile.update');
    Route::get('/trabajo-de-grado', [ThesisController::class, 'show'])->middleware('role:estudiante')->name('thesis.show');
    Route::get('/historia-academica', [AcademicHistoryController::class, 'index'])->middleware('role:estudiante')->name('academic-history.index');
    Route::get('/practica', [InternshipController::class, 'show'])->middleware('role:estudiante')->name('internship.show');

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

    Route::middleware('role:coordinacion')->group(function () {
        Route::redirect('/admin', '/admin/usuarios');
        Route::get('/admin/usuarios', [AdminController::class, 'users'])->name('users.index');
        Route::get('/admin/cargas', [AdminController::class, 'imports'])->name('cargas.index');
        Route::get('/admin/catalogos', [AdminController::class, 'catalogs'])->name('catalogos.index');
        Route::get('/admin/bitacora', [AdminController::class, 'audit'])->name('bitacora.index');
    });
});
