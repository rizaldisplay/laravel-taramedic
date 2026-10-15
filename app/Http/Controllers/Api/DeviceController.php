<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RefreshToken;
use App\Models\TrustedDevice;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Tampilkan semua perangkat terhubung milik user aktif
     */
    public function index()
    {
        $devices = TrustedDevice::where('user_id', auth('api')->id())
            ->orderBy('last_active_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $devices
        ]);
    }

    /**
     * Putuskan akses (Revoke) pada perangkat tertentu
     */
    public function revoke(Request $request, $deviceId)
    {
        $device = TrustedDevice::where('user_id', auth('api')->id())
            ->where('id', $deviceId)
            ->first();

        if (!$device) {
            return response()->json([
                'status'  => false,
                'message' => 'Perangkat tidak ditemukan'
            ], 44);
        }

        // Revoke status trusted
        $device->update(['is_trusted' => false]);

        // Revoke seluruh Refresh Token dari IP/User-Agent yang sesuai perangkat ini
        RefreshToken::where('user_id', auth('api')->id())
            ->where('user_agent', $device->user_agent)
            ->update(['is_revoked' => true]);

        AuditLogService::record(
            auth('api')->id(),
            'device.is_revoked',
            "Akses perangkat '{$device->device_name}' dicabut",
            $request
        );

        return response()->json([
            'status'  => true,
            'message' => 'Akses perangkat berhasil dicabut'
        ]);
    }
}