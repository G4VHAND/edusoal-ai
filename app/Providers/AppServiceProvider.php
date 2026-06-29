<?php

namespace App\Providers;

use App\Models\QuestionSet;
use App\Policies\QuestionSetPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Policy
        Gate::policy(QuestionSet::class, QuestionSetPolicy::class);

        // Rate limiter untuk generate soal
        // Limit dapat dikonfigurasi via GENERATE_RATE_LIMIT di .env
        RateLimiter::for('generate-soal', function (Request $request) {
            $limit = config('ai.rate_limit', 5);

            return Limit::perMinute($limit)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () use ($limit) {
                    return back()
                        ->withErrors(['rate_limit' =>
                            "Terlalu banyak permintaan. Maksimal {$limit} generate soal per menit. "
                            . "Silakan tunggu sebentar.",
                        ]);
                });
        });
    }
}
