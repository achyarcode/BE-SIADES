<?php

use App\Http\Controllers\AdminSignatureController;
use App\Http\Controllers\AdminStampController;
use App\Http\Controllers\Api\StrukturDesaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JenisSuratController;
use App\Http\Controllers\KatalogController;
use App\Http\Controllers\PerangkatDesaController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// URL PUBLIC (Tidak perlu token untuk akses ini)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp'])->middleware('throttle:5,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/katalog', [KatalogController::class, 'publicIndex']);
Route::get('/struktur-desa', [StrukturDesaController::class, 'index']);
Route::get('/struktur-desa/{id}', [StrukturDesaController::class, 'show']);

// URL PROTECTED (Wajib bawa token Sanctum untuk akses ini)
Route::middleware('auth:sanctum')->group(function () {

    // Cek profil user yang sedang login
    Route::get('/me', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'roles' => $request->user()->getRoleNames(),
        ]);
    });

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/jenis-surat', [JenisSuratController::class, 'index']);
    Route::get('/warga/profile', [UserController::class, 'profile']);
    Route::put('/warga/profile', [UserController::class, 'updateProfile']);
    Route::post('/warga/profile/photo', [UserController::class, 'updateProfilePhoto']);
    Route::post('/warga/account/setup', [AuthController::class, 'setupWargaCredentials']);

    Route::middleware(['role:admin|super-admin'])->group(function () {
        // Admin Signatures
        Route::get('/admin/signatures', [AdminSignatureController::class, 'index']);
        Route::post('/admin/signatures', [AdminSignatureController::class, 'store']);
        Route::delete('/admin/signatures/{id}', [AdminSignatureController::class, 'destroy']);
        Route::get('/admin/signatures/{id}/image', [AdminSignatureController::class, 'showImage']);

        // Admin Stamps
        Route::get('/admin/stamps', [AdminStampController::class, 'index']);
        Route::post('/admin/stamps', [AdminStampController::class, 'store']);
        Route::delete('/admin/stamps/{id}', [AdminStampController::class, 'destroy']);
        Route::get('/admin/stamps/{id}/image', [AdminStampController::class, 'showImage']);

        // Struktur Desa
        Route::post('/admin/struktur-desa', [StrukturDesaController::class, 'store']);
        Route::put('/admin/struktur-desa/{id}', [StrukturDesaController::class, 'update']);
        Route::delete('/admin/struktur-desa/{id}', [StrukturDesaController::class, 'destroy']);

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
        Route::post('/admin/jenis-surat', [JenisSuratController::class, 'store']);
        Route::delete('/admin/jenis-surat/{id}', [JenisSuratController::class, 'destroy']);
    });

    // Pengajuan Surat & Download (Warga & Admin)
    Route::get('/warga/dashboard/stats', [DashboardController::class, 'wargaStats']);
    Route::post('/warga/pengajuan-surat', [SuratController::class, 'store']);
    Route::get('/surats/{id}/download', [SuratController::class, 'download']);

    // Katalog (Warga)
    Route::get('/warga/katalog', [KatalogController::class, 'myKatalog']);
    Route::post('/warga/katalog', [KatalogController::class, 'store']);
    Route::patch('/warga/katalog/{id}/status', [KatalogController::class, 'updateWargaStatus']);

    // Manajemen Perangkat Desa (Strictly Super Admin)
    Route::middleware(['role:super-admin'])->group(function () {
        Route::get('/admin/perangkat-desa', [PerangkatDesaController::class, 'index']);
        Route::get('/admin/perangkat-desa/search', [PerangkatDesaController::class, 'search']);
        Route::post('/admin/perangkat-desa/assign', [PerangkatDesaController::class, 'assignRole']);
        Route::post('/admin/perangkat-desa/revoke/{user}', [PerangkatDesaController::class, 'revokeRole']);
    });

    // ROUTE E-KATALOG (Wajib Login)
    Route::post('/katalog', [KatalogController::class, 'store']); // Tambah produk
    Route::put('/katalog/{id}', [KatalogController::class, 'update']); // Edit produk
    Route::delete('/katalog/{id}', [KatalogController::class, 'destroy']); // Hapus produk

});

// Fallback for unauthorized redirects from Sanctum
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');
