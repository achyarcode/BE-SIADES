<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KatalogController;

// URL PUBLIC (Tidak perlu token untuk akses ini)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/katalog', [KatalogController::class, 'publicIndex']);

// URL PROTECTED (Wajib bawa token Sanctum untuk akses ini)
Route::middleware('auth:sanctum')->group(function () {
    
    // Cek profil user yang sedang login
    Route::get('/me', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'roles' => $request->user()->getRoleNames()
        ]);
    });

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin Signatures
    Route::get('/admin/signatures', [\App\Http\Controllers\AdminSignatureController::class, 'index']);
    Route::post('/admin/signatures', [\App\Http\Controllers\AdminSignatureController::class, 'store']);
    Route::delete('/admin/signatures/{id}', [\App\Http\Controllers\AdminSignatureController::class, 'destroy']);

    // Katalog (Admin)
    Route::get('/admin/katalog', [KatalogController::class, 'index']);
    Route::delete('/admin/katalog/{id}', [KatalogController::class, 'destroy']);
    Route::patch('/admin/katalog/{id}/status', [KatalogController::class, 'updateStatus']);

    // Admin Dashboard & Users
    Route::get('/admin/dashboard/stats', [DashboardController::class, 'stats']);
    // Renamed resource: use /admin/users (resource = users)
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users', [UserController::class, 'store']);
    Route::put('/admin/users/{user}', [UserController::class, 'update']);
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);

    // Persetujuan Surat (Admin)
    Route::get('/admin/persetujuan-surat', [SuratController::class, 'index']);
    Route::post('/admin/persetujuan-surat/{id}/approve', [SuratController::class, 'approve']);
    Route::patch('/admin/persetujuan-surat/{id}/reject', [SuratController::class, 'reject']);

    // Pengajuan Surat & Download (Warga & Admin)
    Route::post('/warga/pengajuan-surat', [SuratController::class, 'store']);
    Route::get('/surats/{id}/download', [SuratController::class, 'download']);

    // Katalog (Warga)
    Route::post('/warga/katalog', [KatalogController::class, 'store']);

});

// Fallback for unauthorized redirects from Sanctum
Route::get('/login', function() {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');