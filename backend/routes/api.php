<?php

use App\Http\Controllers\Api\AfiliacionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas de la API REST del Sistema del Sindicato de Choferes.
| Todas las rutas aquí están prefijadas con /api
|
*/

// ─── Rutas Públicas ──────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
});

// ─── Rutas Protegidas (Requieren Token Sanctum) ──────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

    // ─── Módulo de Afiliación (Usuarios, Choferes, Vehículos) ─────────
    Route::prefix('afiliacion')->group(function () {
        Route::get('/', [AfiliacionController::class, 'index'])->name('afiliacion.index');
        Route::get('/auxiliares', [AfiliacionController::class, 'auxiliares'])->name('afiliacion.auxiliares');

        // Rutas Exclusivas para Administradores
        Route::middleware(EnsureUserHasRole::class . ':Administrador')->group(function () {
            Route::post('/registrar', [AfiliacionController::class, 'store'])->name('afiliacion.store');
            Route::put('/{id}', [AfiliacionController::class, 'update'])->name('afiliacion.update');
            Route::patch('/{id}/estado', [AfiliacionController::class, 'toggleEstado'])->name('afiliacion.toggle-estado');
        });
    });
});
