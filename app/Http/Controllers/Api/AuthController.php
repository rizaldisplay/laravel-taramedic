<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        // 1. Validasi input
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 2. Cek limitasi percobaan login (Brute-force protection)
        $this->ensureIsNotRateLimited($request);

        // 3. Autentikasi
        if (! Auth::attempt($request->only('email', 'password'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // 4. Bersihkan rate limiter jika login sukses
        RateLimiter::clear($this->throttleKey($request));

        $user = $request->user();

        // 5. Buat API Token (Menggunakan Laravel Sanctum)
        // Pastikan model User sudah menggunakan trait HasApiTokens
        $token = $user->createToken('saas-access-token')->plainTextToken;

        // 6. Tentukan rute default berdasarkan role untuk panduan frontend
        $intendedRolePath = $user->role->canAccessAdmin() 
            ? 'admin.dashboard' 
            : 'operator.panel';

        return response()->json([
            'message' => 'Login berhasil.',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'intended_route' => $intendedRolePath,
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Hapus token yang saat ini digunakan
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.'
        ]);
    }

    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => ["Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."],
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());
    }
}