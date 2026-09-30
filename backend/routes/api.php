<?php

use App\Http\Controllers\Api\AfiliacionController;
use App\Http\Controllers\Api\AsistenciaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SancionController;
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

        // Exclusivo para Administradores
        Route::middleware(EnsureUserHasRole::class . ':Administrador')->group(function () {
            Route::post('/registrar', [AfiliacionController::class, 'store'])->name('afiliacion.store');
            Route::put('/{id}', [AfiliacionController::class, 'update'])->name('afiliacion.update');
            Route::patch('/{id}/estado', [AfiliacionController::class, 'toggleEstado'])->name('afiliacion.toggle-estado');
        });
    });

    // ─── Módulo de Asistencias e Inspección ─────────────────────────
    Route::prefix('asistencias')->middleware(EnsureUserHasRole::class . ':Inspector,Administrador')->group(function () {
        Route::get('/hoy', [AsistenciaController::class, 'hoy'])->name('asistencias.hoy');
        Route::post('/guardar', [AsistenciaController::class, 'guardar'])->name('asistencias.guardar');
        Route::get('/historial-fecha', [AsistenciaController::class, 'historialPorFecha'])->name('asistencias.historial-fecha');
    });

    // ─── Módulo de Sanciones e Infracciones ─────────────────────────
    Route::prefix('sanciones')->middleware(EnsureUserHasRole::class . ':Inspector,Administrador')->group(function () {
        Route::get('/', [SancionController::class, 'index'])->name('sanciones.index');
        Route::post('/registrar', [SancionController::class, 'store'])->name('sanciones.store');
        Route::put('/{id}', [SancionController::class, 'update'])->name('sanciones.update');
        Route::get('/auxiliares', [SancionController::class, 'auxiliares'])->name('sanciones.auxiliares');
    });

    // ─── Módulo de Gestión de Obligaciones (Jefe de Grupo / Admin) ───
    Route::prefix('obligaciones')->middleware(EnsureUserHasRole::class . ':Jefe de Grupo,Administrador')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\ObligacionController::class, 'index'])->name('obligaciones.index');
        Route::post('/crear', [\App\Http\Controllers\Api\ObligacionController::class, 'store'])->name('obligaciones.store');
        Route::put('/{id}', [\App\Http\Controllers\Api\ObligacionController::class, 'update'])->name('obligaciones.update');
        Route::get('/{id}/detalles', [\App\Http\Controllers\Api\ObligacionController::class, 'detalles'])->name('obligaciones.detalles');
        Route::get('/auxiliares', [\App\Http\Controllers\Api\ObligacionController::class, 'auxiliares'])->name('obligaciones.auxiliares');
    });

    // ─── Módulo de Procesamiento de Cobros (Tesorero / Jefe / Admin) ─
    Route::prefix('cobros')->middleware(EnsureUserHasRole::class . ':Tesorero,Jefe de Grupo,Administrador')->group(function () {
        Route::get('/choferes', [\App\Http\Controllers\Api\CobroController::class, 'choferesElegibles'])->name('cobros.choferes');
        Route::get('/estado-cuenta/{choferId}', [\App\Http\Controllers\Api\CobroController::class, 'estadoCuenta'])->name('cobros.estado-cuenta');
        Route::post('/procesar', [\App\Http\Controllers\Api\CobroController::class, 'store'])->name('cobros.procesar');
        Route::get('/historial', [\App\Http\Controllers\Api\CobroController::class, 'historial'])->name('cobros.historial');
        Route::post('/{id}/anular-inmediato', [\App\Http\Controllers\Api\CobroController::class, 'anularInmediato'])->name('cobros.anular-inmediato');
        Route::post('/{id}/solicitar-cambio', [\App\Http\Controllers\Api\CobroController::class, 'solicitarCambio'])->name('cobros.solicitar-cambio');
    });

    // ─── Módulo de Solicitudes de Cambio de Cobro (Chofer / Jefe / Admin / Tesorero) ───
    Route::prefix('solicitudes-cambio')->group(function () {
        Route::get('/pendientes', [\App\Http\Controllers\Api\CobroController::class, 'solicitudesPendientes'])->name('solicitudes.pendientes');
        Route::post('/{id}/responder', [\App\Http\Controllers\Api\CobroController::class, 'responderSolicitud'])->name('solicitudes.responder');
    });

    // ─── Módulo de Notificaciones (Para todos los usuarios autenticados) ───
    Route::prefix('notificaciones')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificacionController::class, 'index'])->name('notificaciones.index');
        Route::patch('/{id}/leer', [\App\Http\Controllers\Api\NotificacionController::class, 'marcarLeida'])->name('notificaciones.marcar-leida');
        Route::patch('/marcar-todas', [\App\Http\Controllers\Api\NotificacionController::class, 'marcarTodas'])->name('notificaciones.marcar-todas');
    });

    // ─── Módulo de Consulta de Auditorías e Historial ────────────────
    Route::prefix('auditorias')->middleware(EnsureUserHasRole::class . ':Jefe de Grupo,Inspector,Tesorero,Administrador')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\AuditoriaController::class, 'index'])->name('auditorias.index');
        Route::get('/auxiliares', [\App\Http\Controllers\Api\AuditoriaController::class, 'auxiliares'])->name('auditorias.auxiliares');
    });

    // ─── Módulo de Perfil & Vehículos del Chofer / Propietario ───────
    Route::get('/chofer/mi-perfil', [\App\Http\Controllers\Api\ChoferPerfilController::class, 'miPerfil'])->name('chofer.mi-perfil');
    Route::get('/chofer/mi-historial', [\App\Http\Controllers\Api\ChoferPerfilController::class, 'miHistorial'])->name('chofer.mi-historial');

    // ─── Módulo de Rotación de Paradas (Sincronización Web y Móvil) ──
    Route::prefix('rotacion')->group(function () {
        Route::get('/payload', [\App\Http\Controllers\Api\RotacionController::class, 'payload'])->name('rotacion.payload');
        Route::get('/itinerario', [\App\Http\Controllers\Api\RotacionController::class, 'itinerario'])->name('rotacion.itinerario');
    });
});

