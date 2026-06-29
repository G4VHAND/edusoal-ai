<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Cek apakah user masih punya quota generate bulan ini.
 * Dipasang di route POST /generate-soal.
 */
class CheckQuota
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user->hasQuota()) {
            $remaining = $user->remainingQuota();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Quota generate soal bulan ini sudah habis.',
                    'remaining' => $remaining,
                ], 429);
            }

            return back()->withErrors([
                'quota' => "Quota generate soal bulan ini sudah habis (sisa: {$remaining}). "
                    . "Upgrade plan untuk mendapatkan lebih banyak quota.",
            ]);
        }

        return $next($request);
    }
}
