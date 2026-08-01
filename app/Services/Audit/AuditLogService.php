<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;

class AuditLogService
{
    /**
     * @param  int|null  $userId  Override user — WAJIB diisi manual saat dipanggil
     *                            dari dalam Job/queue, karena Auth::user() tidak
     *                            tersedia di proses queue worker.
     * @param  int|null  $schoolId  Override sekolah, dipakai bersamaan dengan $userId
     *                              saat dipanggil dari Job.
     */
    public static function log(
        string $module,
        string $event,
        string $description,
        array $properties = [],
        ?int $userId = null,
        ?int $schoolId = null,
    ): void {
        $user = Auth::user();

        $agent = new Agent;

        AuditLog::create([
            'user_id' => $userId ?? $user?->id,
            'school_id' => $schoolId ?? $user?->school_id,

            'module' => $module,
            'event' => $event,
            'description' => $description,

            // request()->ip() aman dipanggil di context queue juga — kalau
            // tidak ada HTTP request nyata (dipanggil dari Job), Laravel
            // tetap mengembalikan instance Request default (biasanya null/
            // '127.0.0.1'), tidak melempar error.
            'ip_address' => request()?->ip(),

            'browser' => $agent->browser(),
            'device' => $agent->device(),

            'properties' => $properties,
        ]);
    }

    /**
     * Bandingkan array atribut "sebelum" dan "sesudah", kembalikan hanya
     * field yang benar-benar berubah dalam format:
     * ['field' => ['before' => ..., 'after' => ...]]
     *
     * Dipakai supaya audit log update bisa menjawab "apa yang berubah?",
     * bukan cuma "ada yang berubah".
     */
    public static function diff(array $before, array $after): array
    {
        $changes = [];

        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;

            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'before' => $oldValue,
                    'after' => $newValue,
                ];
            }
        }

        return $changes;
    }
}
