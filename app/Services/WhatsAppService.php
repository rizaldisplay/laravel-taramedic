<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Kirim OTP via WhatsApp Gateway
     */
    public function sendOtp(string $targetPhone, string $otp): bool
    {
        $message = "Kode OTP Login Klinik Anda adalah: *{$otp}*.\n\n"
            . "Jangan berikan kode ini kepada siapapun. "
            . "Valid selama 5 menit.";

        try {
            $response = Http::withHeaders([
                'Authorization' => config('services.fonnte.key'),
            ])->post('https://api.fonnte.com/send', [
                'target'  => $targetPhone,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp OTP berhasil dikirim', [
                    'target' => $targetPhone,
                ]);

                return true;
            }

            Log::error('WhatsApp OTP gagal dikirim', [
                'target' => $targetPhone,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;

        } catch (\Throwable $e) {
            Log::error('WhatsApp OTP exception', [
                'target' => $targetPhone,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}