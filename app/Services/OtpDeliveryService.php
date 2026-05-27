<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OtpDeliveryService
{
    public function sendPasswordResetOtp(string $phone, string $otp): void
    {
        $message = "Kode OTP SIADES Anda: {$otp}. Berlaku 10 menit. Jangan berikan kode ini kepada siapa pun.";
        $channel = config('services.otp.channel', 'log');

        if ($channel === 'gowa') {
            $this->sendViaGowa($phone, $message);

            return;
        }

        Log::info('Password reset OTP generated.', [
            'phone' => $this->maskPhone($phone),
            'otp' => $otp,
            'channel' => $channel,
        ]);
    }

    private function sendViaGowa(string $phone, string $message): void
    {
        $username = config('services.gowa.username');
        $password = config('services.gowa.password');

        if (! is_string($username) || trim($username) === '' || ! is_string($password) || trim($password) === '') {
            throw new RuntimeException('GOWA basic auth credentials are not configured.');
        }

        $request = Http::withBasicAuth($username, $password)
            ->asJson()
            ->timeout((int) config('services.gowa.timeout', 10));

        $deviceId = config('services.gowa.device_id');

        if (is_string($deviceId) && trim($deviceId) !== '') {
            $request = $request->withHeader('X-Device-Id', trim($deviceId));
        }

        $response = $request->post((string) config('services.gowa.endpoint'), [
            'phone' => $this->normalizeForGowa($phone),
            'message' => $message,
            'is_forwarded' => false,
        ]);

        if (! $response->successful() || ! in_array((string) $response->json('code'), ['SUCCESS', '200'], true)) {
            Log::warning('GOWA rejected OTP message request.', [
                'status' => $response->status(),
                'code' => $response->json('code'),
                'message' => $response->json('message'),
                'device_id_configured' => is_string($deviceId) && trim($deviceId) !== '',
            ]);

            throw new RuntimeException('GOWA failed to queue the OTP message.');
        }
    }

    private function normalizeIndonesianPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        return $digits;
    }

    private function normalizeForGowa(string $phone): string
    {
        $digits = $this->normalizeIndonesianPhone($phone);

        return $digits.'@s.whatsapp.net';
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 7) {
            return '***';
        }

        return substr($phone, 0, 4).'****'.substr($phone, -3);
    }
}
