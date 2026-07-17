<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;

class AuditLogService
{
    public static function log(
        string $module,
        string $event,
        string $description,
        array $properties = []
    ): void {
        $user = Auth::user();

        $agent = new Agent();

        AuditLog::create([
            'user_id' => $user?->id,
            'school_id' => $user?->school_id,

            'module' => $module,
            'event' => $event,
            'description' => $description,

            'ip_address' => request()->ip(),

            'browser' => $agent->browser(),
            'device' => $agent->device(),

            'properties' => $properties,
        ]);
    }
}