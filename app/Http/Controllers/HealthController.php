<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Liveness + dependency check used by Render, CI smoke tests and uptime monitors.
     */
    public function __invoke(): JsonResponse
    {
        $database = 'ok';

        try {
            DB::select('select 1');
        } catch (Throwable) {
            $database = 'down';
        }

        $healthy = $database === 'ok';

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => ['database' => $database],
            'app' => config('app.name'),
            'version' => config('eventpass.version'),
            'time' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
