<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\RefreshToken;
use App\Models\User;
use App\Models\TrustedDevice;
use App\Services\AuditLogService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected $waService;

    public function __construct(WhatsAppService $waService)
    {
        $this->waService = $waService;
    }

    /**
     * Step 1: Login Username + Password -> Memicu Pengiriman OTP WA
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        // Validasi Kredensial Pengguna
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Username atau password salah'
            ], 401);
        }

        // Generate 6 Digit Angka OTP Random
        $otpRandom = (string) rand(100000, 999999);

        // Nonaktifkan OTP lama yang belum terpakai milik user ini
        OtpCode::where('user_id', $user->id)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Simpan OTP Baru ke Database (Masa berlaku 5 Menit)
        OtpCode::create([
            'user_id'    => $user->id,
            'otp'        => $otpRandom,
            'type'       => 'login',
            'expires_at' => now()->addMinutes(5),
            'is_used'    => false,
        ]);

        // Kirim Kode OTP via WhatsApp Service
        $this->waService->sendOtp($user->phone_number, $otpRandom);

        // Audit Log: Request OTP
        AuditLogService::record(
            $user->id,
            'auth.otp_requested',
            'Kode OTP dikirimkan via WhatsApp',
            $request
        );

        return response()->json([
            'status'  => true,
            'message' => 'Kode OTP berhasil dikirim ke nomor WhatsApp Anda',
            'data'    => [
                'user_id'      => $user->id,
                'phone_number' => substr($user->phone_number, 0, 4) . '****' . substr($user->phone_number, -3)
            ]
        ]);
    }

    /**
     * Step 2: Verifikasi OTP -> Menerbitkan Access Token & Refresh Token
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp'     => 'required|string|size:6',
        ]);

        // Cek validitas OTP di database
        $otpRecord = OtpCode::where('user_id', $request->user_id)
            ->where('otp', $request->otp)
            ->where('is_used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status'  => false,
                'message' => 'Kode OTP salah atau sudah kadaluwarsa'
            ], 422);
        }

        // Tandai OTP sudah terpakai
        $otpRecord->update(['is_used' => true]);

        $user = User::find($request->user_id);

        // Track Perangkat Pengguna
        $this->trackDevice($user, $request);

        // Generasi Access Token (JWT) & Refresh Token
        $accessToken = JWTAuth::fromUser($user);
        $refreshToken = $this->generateRefreshToken($user, $request);

        return $this->respondWithTokens($accessToken, $refreshToken->token, $user);
    }

    /**
     * Step 3: Refresh Access Token menggunakan Refresh Token
     */
    public function refresh(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $tokenRecord = RefreshToken::where('token', $request->refresh_token)
            ->where('is_revoked', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord) {
            return response()->json([
                'status'  => false,
                'message' => 'Refresh token tidak valid atau sudah kadaluwarsa'
            ], 401);
        }

        // Token Rotation: Invalidate token lama
        $tokenRecord->update(['is_revoked' => true]);

        // Buat pasangan token baru
        $user = $tokenRecord->user;
        $newAccessToken = JWTAuth::fromUser($user);
        $newRefreshToken = $this->generateRefreshToken($user, $request);

        return $this->respondWithTokens($newAccessToken, $newRefreshToken->token, $user);
    }

    /**
     * Step 4: Logout & Invalidate Tokens
     */
    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (\Throwable $th) {
            // Abaikan jika token JWT memang sudah kadaluwarsa
        }

        if ($request->has('refresh_token')) {
            RefreshToken::where('token', $request->refresh_token)
                ->update(['is_revoked' => true]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Berhasil logout'
        ]);
    }

    /**
     * Step 5: Ambil Data Pengguna Aktif
     */
    public function me()
    {
        return response()->json([
            'status' => true,
            'data'   => auth('api')->user()
        ]);
    }

    /**
     * Helper: Format Output JSON Token
     */
    protected function respondWithTokens($accessToken, $refreshToken, $user)
    {
        return response()->json([
            'status'  => true,
            'message' => 'Autentikasi berhasil',
            'data'    => [
                'user' => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'username' => $user->username,
                    'email'    => $user->email,
                    'role'     => $user->role,
                ],
                'authorization' => [
                    'access_token'  => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type'    => 'bearer',
                    'expires_in'    => JWTAuth::factory()->getTTL() * 60
                ]
            ]
        ]);
    }


    /**
     * Helper Track & Sync Device Pengguna
     */
    protected function trackDevice(User $user, Request $request): TrustedDevice
    {
        // Parameter device_id dikirim via Header atau Body (misal: UUID device dari mobile app/frontend)
        $deviceId = $request->header('X-Device-Id') ?? md5($request->userAgent() . $request->ip());
        $deviceName = $request->header('X-Device-Name') ?? $request->userAgent();

        return TrustedDevice::updateOrCreate(
            [
                'user_id'   => $user->id,
                'device_id' => $deviceId,
            ],
            [
                'device_name'    => Str::limit($deviceName, 100),
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
                'is_trusted'     => true,
                'last_active_at' => now(),
            ]
        );
    }

    /**
     * Helper: Simpan Refresh Token Baru di DB
     */
    protected function generateRefreshToken(User $user, Request $request): RefreshToken
    {
        return RefreshToken::create([
            'user_id'    => $user->id,
            'token'      => Str::random(80),
            'expires_at' => now()->addDays(30),
            'is_revoked' => false,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}