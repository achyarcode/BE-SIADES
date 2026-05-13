<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    
    // 1. FITUR REGISTRASI (KHUSUS WARGA)
    
    public function register(Request $request)
    {
        // a. Validasi input dari Frontend
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:255',
            'password' => 'required|string|min:6',
            'nik' => 'required|string|unique:users,nik|max:16',
            'no_kk' => 'nullable|string|max:16',
            'no_telp' => 'nullable|string|max:15',
        ]);

        // b. Buat User Baru di Database
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => $request->password, // Otomatis di-enkripsi karena ada casts() di Model User
            'nik' => $request->nik,
            'no_kk' => $request->no_kk,
            'no_telp' => $request->no_telp,
        ]);

        // c. Berikan Hak Akses (Role) Otomatis sebagai warga
        $user->assignRole('warga');

        // d. Buat Token Sanctum (Agar user langsung login setelah daftar)
        $token = $user->createToken('siades-auth-token')->plainTextToken;

        // e. Kirim respon JSON ke Frontend
        return response()->json([
            'message' => 'Registrasi berhasil',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'nik' => $user->nik,
                'roles' => $user->getRoleNames(),
            ],
            'token' => $token
        ], 201); // 201 adalah kode HTTP untuk "Created" (Berhasil Dibuat)
    }

    
    // 2. FITUR LOGIN
    
    public function login(Request $request)
    {
        // a. Validasi: Sekarang kita pakai username, bukan NIK
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // b. Cari user berdasarkan username
        $user = User::where('username', $request->username)->first();

        // c. Cek apakah user ada dan passwordnya benar
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Username atau Password salah.'
            ], 401); // 401 adalah kode HTTP untuk "Unauthorized" (Ditolak)
        }

        // d. Buat Token Sanctum
        $token = $user->createToken('siades-auth-token')->plainTextToken;

        // e. Kirim respon JSON ke Frontend
        return response()->json([
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'nik' => $user->nik,
                'no_kk' => $user->no_kk,
                'roles' => $user->getRoleNames(), // Mengirimkan info jabatan (misal: ["Warga"])
            ],
            'token' => $token
        ], 200); // 200 adalah kode HTTP untuk "OK" (Sukses)
    }

    
    // 3. FITUR LOGOUT
    
    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan saat ini (Logout)
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ], 200);
    }
}