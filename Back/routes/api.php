<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CanchaController2;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Estructura:
|   1. Rutas públicas (sin autenticación)
|   2. Rutas protegidas (requieren token)
|--------------------------------------------------------------------------
*/

// 🟢 1. Rutas Públicas
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Canchas
Route::get('/canchas2', [ReservaController::class, 'getCanchas']);
Route::apiResource('canchas2', CanchaController2::class)->only(['index', 'show']);

// Disponibilidad y horarios
Route::get('/horarios', [ReservaController::class, 'getHorarios']);
Route::get('/disponibilidad', [ReservaController::class, 'getDisponibilidad']);
Route::get('/disponibilidad-mes', [ReservaController::class, 'getDisponibilidadMes']);

// 🔒 2. Rutas Protegidas (Requieren token válido)
Route::middleware('auth:api')->group(function () {

    // 🔹 Reservas
    Route::get('/reservas', [ReservaController::class, 'index']);
    Route::get('/reservations', [ReservaController::class, 'getReservasActivasPorUsuario']);
    Route::post('/reservas', [ReservaController::class, 'store']);
    Route::put('/reservas/{reserva}', [ReservaController::class, 'update']);
    Route::delete('/reservas/{reserva}', [ReservaController::class, 'destroy']);

    // 🔹 Administración de Reservas
    Route::get('/reservas/activas', [ReservaController::class, 'getReservasActivas']);
    Route::get('/reservas/pendientes', [ReservaController::class, 'getReservasPendientes']);
    Route::get('/reservas/canceladas', [ReservaController::class, 'getReservasCanceladas']);
    Route::get('/reservas/metrics', [ReservaController::class, 'getMetrics']);
    Route::get('/reservas/ingresos', [ReservaController::class, 'getIngresosMensuales']);
    Route::put('/reservas/{id}/estado', [ReservaController::class, 'actualizarEstado']);

    // 🔹 Administración de Usuarios
    Route::get('/usuarios-registrados', [UserController::class, 'getUsuariosRegistrados']); // ✅ único endpoint para el front
    Route::put('/usuarios/{id}', [UserController::class, 'actualizarTelefono']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);


    // 🔹 Administración de Canchas
    Route::apiResource('canchas2', CanchaController2::class)->except(['index', 'show']);
});
