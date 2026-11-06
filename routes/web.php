<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuditController::class, 'home']);
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'form'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:8,1');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:5,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout']);
    Route::get('/audits', [AuditController::class, 'history']);
    Route::post('/audits', [AuditController::class, 'store'])->middleware('throttle:3,1');
    Route::get('/audits/{audit}', [AuditController::class, 'show'])->name('audit.show');
    Route::get('/audits/{audit}/export', [AuditController::class, 'export']);
});
