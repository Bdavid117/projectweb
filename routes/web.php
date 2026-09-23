<?php

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

Route::get('/login', [LoginController::class, 'show'])->name('login.show');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');

Route::get('/panel', [PanelController::class, 'index'])->name('panel.index');

Route::get('/estudiantes', [StudentController::class, 'index'])->name('students.index');
Route::get('/estudiantes/{id}', [StudentController::class, 'show'])->name('students.show');

Route::get('/solicitudes/nueva', [RequestController::class, 'create'])->name('requests.create');
Route::post('/solicitudes/nueva', [RequestController::class, 'store'])->name('requests.store');
