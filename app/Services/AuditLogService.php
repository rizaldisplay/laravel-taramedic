<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogService
{
    public static function record(?int $userId, string $action, string $description, ?Request $request = null, ?array $payload = null): AuditLog
    {
        $request = $request ?? request();

        return AuditLog::create([
            'user_id'     => $userId,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'payload'     => $payload,
        ]);
    }
}