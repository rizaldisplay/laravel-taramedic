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
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $waService;

    public function __construct(WhatsAppService $waService)
    {
        $this->waService = $waService;
    }

    /**
     * Step 1: Login Username + Password
     * Jika 2FA aktif -> Kirim OTP WA. Jika tidak -> Langsung terbitkan Token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        // 1. Validasi Kredensial Pengguna
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Username atau password salah'
            ], 401);
        }

        // 2. Cek Status 2FA Pengguna
        if ($user->two_factor_enabled) {
            // --- ALUR DENGAN 2FA (KIRIM OTP) ---
            
            $otpRandom = (string) rand(100000, 999999);

            // Nonaktifkan OTP lama
            OtpCode::where('user_id', $user->id)
                ->where('is_used', false)
                ->update(['is_used' => true]);

            // Simpan OTP Baru
            OtpCode::create([
                'user_id'    => $user->id,
                'otp'        => $otpRandom,
                'type'       => 'login',
                'expires_at' => now()->addMinutes(5),
                'is_used'    => false,
            ]);

            $this->waService->sendOtp($user->phone_number, $otpRandom);

            AuditLogService::record(
                $user->id,
                'auth.otp_requested',
                'Kode OTP dikirimkan via WhatsApp',
                $request
            );

            return response()->json([
                'status'  => true,
                'message' => 'Kode OTP berhasil dikirim ke nomor WhatsApp Anda',
                'is_2fa'  => true, // Flag untuk memberitahu frontend agar memunculkan form OTP
                'data'    => [
                    'user_id'      => $user->id,
                    'phone_number' => substr($user->phone_number, 0, 4) . '****' . substr($user->phone_number, -3)
                ]
            ]);
        }

        // --- ALUR TANPA 2FA (LANGSUNG LOGIN) ---
        
        // Track Perangkat Pengguna
        $this->trackDevice($user, $request);

        // Generasi Access Token (JWT) & Refresh Token
        $accessToken = JWTAuth::fromUser($user);
        $refreshToken = $this->generateRefreshToken($user, $request);

        AuditLogService::record(
            $user->id,
            'auth.login_success',
            'User berhasil login (Tanpa 2FA)',
            $request
        );

        // Menggunakan helper respondWithTokens yang sudah Anda buat
        return $this->respondWithTokens($accessToken, $refreshToken->token, $user);
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
        // Load relasi roles agar response me() memiliki data akses peran user
        $user = User::with('roles')->find(auth('api')->id());
        
        return response()->json([
            'status' => true,
            'data'   => $user
        ]);
    }

    /**
     * Helper: Format Output JSON Token
     */
    protected function respondWithTokens($accessToken, $refreshToken, $user)
    {
        // Load relasi roles jika belum diload
        $user->loadMissing('roles');
        
        return response()->json([
            'status'  => true,
            'message' => 'Autentikasi berhasil',
            'data'    => [
                'user' => [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'username' => $user->username,
                    'email'    => $user->email,
                    // Mengambil array role slugs, contoh: ['super_admin', 'dokter']
                    'roles'    => $user->roles->pluck('slug'), 
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
        // Ambil device_id dengan logika yang sama dengan trackDevice()
        $deviceId = $request->header('X-Device-Id') ?? md5($request->userAgent() . $request->ip());

        return RefreshToken::create([
            'user_id'    => $user->id,
            'token'      => Str::random(80),
            'device_id'  => $deviceId, // TAMBAHKAN INI AGAR TIDAK SQL ERROR
            'expires_at' => now()->addDays(30),
            'is_revoked' => false,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}