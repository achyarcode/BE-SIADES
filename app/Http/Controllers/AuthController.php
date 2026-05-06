<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validasi input dari Frontend (Harus ada NIK dan Password)
        $request->validate([
            'nik' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. Cari user berdasarkan NIK
        $user = User::where('nik', $request->nik)->first();

        // 3. Cek apakah user ada dan passwordnya benar
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'NIK atau Password salah.'
            ], 401);
        }

        // 4. Buat Token Sanctum
        $token = $user->createToken('siades-auth-token')->plainTextToken;

        // 5. Kirim respon JSON ke Frontend
        return response()->json([
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'nik' => $user->nik,
                'name' => $user->name,
                'no_kk' => $user->no_kk,
                'roles' => $user->getRoleNames(), // Mengirimkan info jabatan (misal: ["super-admin"])
            ],
            'token' => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan (Logout)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ], 200);
    }
}