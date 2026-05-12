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
Route::get('/katalog', [KatalogController::class, 'index']);

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

    // Admin Dashboard & Users
    Route::get('/admin/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/admin/warga', [UserController::class, 'index']);

    // Persetujuan Surat (Admin)
    Route::get('/admin/persetujuan-surat', [SuratController::class, 'index']);
    Route::post('/admin/persetujuan-surat/{id}/approve', [SuratController::class, 'approve']);
    Route::patch('/admin/persetujuan-surat/{id}/reject', [SuratController::class, 'reject']);

    // Pengajuan Surat (Warga)
    Route::post('/warga/pengajuan-surat', [SuratController::class, 'store']);

    // ROUTE E-KATALOG (Wajib Login)
    Route::post('/katalog', [KatalogController::class, 'store']); // Tambah produk
    Route::put('/katalog/{id}', [KatalogController::class, 'update']); // Edit produk
    Route::delete('/katalog/{id}', [KatalogController::class, 'destroy']); // Hapus produk

});

// Fallback for unauthorized redirects from Sanctum
Route::get('/login', function() {
    return response()->json(['message' => 'Unauthenticated.'], 401);
})->name('login');