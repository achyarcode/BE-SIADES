<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// URL PUBLIC (Tidak perlu token untuk akses ini)
Route::post('/login', [AuthController::class, 'login']);

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

});