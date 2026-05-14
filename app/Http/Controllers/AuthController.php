<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\WargaSetupCredentialsRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const RESET_OTP_TTL_MINUTES = 10;

    private const RESET_TOKEN_TTL_MINUTES = 15;

    private const MAX_FORGOT_ATTEMPTS_PER_MINUTE = 3;

    private const MAX_OTP_VERIFY_ATTEMPTS = 5;

    private const OTP_VERIFY_LOCK_MINUTES = 10;

    // 1. FITUR REGISTRASI (KHUSUS WARGA)

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        // b. Buat User Baru di Database
        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => $validated['password'],
            'nik' => $validated['nik'],
            'no_kk' => $validated['no_kk'] ?? null,
            'no_telp' => $validated['no_telp'] ?? null,
            'jenis_kelamin' => $validated['jenisKelamin'] === 'L' ? 'Laki-laki' : 'Perempuan',
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
            'token' => $token,
        ], 201); // 201 adalah kode HTTP untuk "Created" (Berhasil Dibuat)
    }

    // 2. FITUR LOGIN

    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        // b. Cari user berdasarkan username
        $user = User::where('username', $validated['username'])->first();

        // c. Cek apakah user ada dan passwordnya benar
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            if (preg_match('/^\d{16}$/', $validated['username']) && User::where('nik', $validated['username'])->exists()) {
                return $this->error('Gunakan username akun Anda, bukan NIK.', 401);
            }

            return $this->error('Username atau Password salah.', 401);
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
                'mustUpdateCredentials' => (bool) $user->must_update_credentials,
                'roles' => $user->getRoleNames(), // Mengirimkan info jabatan (misal: ["Warga"])
            ],
            'token' => $token,
        ], 200); // 200 adalah kode HTTP untuk "OK" (Sukses)
    }

    // 3. FITUR LOGOUT

    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return $this->success('Logout berhasil');
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'no_telp' => ['required', 'regex:/^08\d{8,11}$/'],
        ]);

        $forgotRateKey = $this->forgotRateKey($validated['no_telp'], $request->ip() ?? 'unknown');
        if (RateLimiter::tooManyAttempts($forgotRateKey, self::MAX_FORGOT_ATTEMPTS_PER_MINUTE)) {
            $seconds = RateLimiter::availableIn($forgotRateKey);

            return $this->error("Terlalu sering meminta OTP. Coba lagi dalam {$seconds} detik.", 429);
        }
        RateLimiter::hit($forgotRateKey, 60);

        $user = User::where('no_telp', $validated['no_telp'])->first();

        if (! $user) {
            return $this->error('Nomor HP tidak terdaftar.', 404);
        }

        $otp = (string) random_int(100000, 999999);
        $key = $this->otpCacheKey($validated['no_telp']);

        Cache::put($key, [
            'user_id' => $user->id,
            'otp_hash' => Hash::make($otp),
        ], now()->addMinutes(self::RESET_OTP_TTL_MINUTES));

        $data = [
            'expires_in_seconds' => self::RESET_OTP_TTL_MINUTES * 60,
        ];

        if (app()->environment(['local', 'development'])) {
            $data['debug_otp'] = $otp;
        }

        return $this->success('Kode OTP berhasil dibuat.', $data);
    }

    public function verifyResetOtp(Request $request)
    {
        $validated = $request->validate([
            'no_telp' => ['required', 'regex:/^08\d{8,11}$/'],
            'otp' => ['required', 'digits:6'],
        ]);

        $verifyRateKey = $this->verifyRateKey($validated['no_telp']);
        if (RateLimiter::tooManyAttempts($verifyRateKey, self::MAX_OTP_VERIFY_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($verifyRateKey);

            return $this->error("Terlalu banyak percobaan OTP salah. Coba lagi dalam {$seconds} detik.", 429);
        }

        $key = $this->otpCacheKey($validated['no_telp']);
        $payload = Cache::get($key);

        if (! $payload || ! isset($payload['otp_hash'], $payload['user_id'])) {
            return $this->error('OTP tidak valid atau sudah kedaluwarsa.', 400);
        }

        if (! Hash::check($validated['otp'], $payload['otp_hash'])) {
            RateLimiter::hit($verifyRateKey, self::OTP_VERIFY_LOCK_MINUTES * 60);

            return $this->error('OTP tidak valid atau sudah kedaluwarsa.', 400);
        }

        RateLimiter::clear($verifyRateKey);
        Cache::forget($key);

        $resetToken = Str::random(64);
        Cache::put($this->tokenCacheKey($resetToken), [
            'user_id' => $payload['user_id'],
        ], now()->addMinutes(self::RESET_TOKEN_TTL_MINUTES));

        return $this->success('OTP terverifikasi.', [
            'reset_token' => $resetToken,
            'expires_in_seconds' => self::RESET_TOKEN_TTL_MINUTES * 60,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $key = $this->tokenCacheKey($validated['reset_token']);
        $payload = Cache::get($key);

        if (! $payload || ! isset($payload['user_id'])) {
            return $this->error('Token reset tidak valid atau sudah kedaluwarsa.', 400);
        }

        $user = User::find($payload['user_id']);

        if (! $user) {
            Cache::forget($key);

            return $this->error('User tidak ditemukan.', 404);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        $user->tokens()->delete();
        Cache::forget($key);

        return $this->success('Password berhasil diubah.');
    }

    public function setupWargaCredentials(WargaSetupCredentialsRequest $request)
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('warga')) {
            return $this->error('Akses ditolak.', 403);
        }

        $validated = $request->validated();

        if ($validated['username'] === $user->nik) {
            return $this->error('Username tidak boleh sama dengan NIK.', 422);
        }

        $user->update([
            'username' => $validated['username'],
            'password' => $validated['password'],
            'must_update_credentials' => false,
            'credentials_updated_at' => now(),
        ]);

        $request->user()?->currentAccessToken()?->delete();
        $token = $user->createToken('siades-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Akun berhasil diperbarui.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'mustUpdateCredentials' => false,
                'roles' => $user->getRoleNames(),
            ],
        ]);
    }

    private function otpCacheKey(string $phone): string
    {
        return 'password_reset:otp:'.$phone;
    }

    private function tokenCacheKey(string $token): string
    {
        return 'password_reset:token:'.hash('sha256', $token);
    }

    private function forgotRateKey(string $phone, string $ip): string
    {
        return "password_reset:forgot:{$phone}:{$ip}";
    }

    private function verifyRateKey(string $phone): string
    {
        return "password_reset:verify:{$phone}";
    }
}
