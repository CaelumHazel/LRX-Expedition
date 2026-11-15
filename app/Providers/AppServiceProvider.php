<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $queryCount = 0;
        $totalQueryTime = 0;

        DB::listen(function ($query) use (&$queryCount, &$totalQueryTime) {
            $queryCount++;
            $totalQueryTime += $query->time;

            if ($query->time >= 50) {
                Log::warning('SLOW SQL QUERY', [
                    'sql' => $query->sql,
                    'time_ms' => $query->time,
                ]);
            }
        });

        app()->terminating(function () use (&$queryCount, &$totalQueryTime) {
            Log::info('REQUEST SQL PROFILE', [
                'method' => request()->method(),
                'url' => request()->path(),
                'query_count' => $queryCount,
                'total_query_time_ms' => round($totalQueryTime, 2),
            ]);
        });
    }
}
