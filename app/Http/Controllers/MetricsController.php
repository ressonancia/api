<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $apps = $user->apps;

        $metrics = [];
        $twentyFourHoursAgo = Carbon::now()->subHours(24)->getTimestamp();

        foreach ($apps as $app) {
            $currentConnections = DB::table('pulse_values')
                ->where('type', 'reverb_connections')
                ->where('key', $app->app_id)
                ->value('value') ?? 0;

            $messagesSentAgg = DB::table('pulse_aggregates')
                ->where('type', 'reverb_message')
                ->where('key', $app->app_id)
                ->where('bucket', '>=', $twentyFourHoursAgo)
                ->where('aggregate', 'count')
                ->sum('value');

            $metrics[] = [
                'connections' => (int) $currentConnections,
                'messages' => (int) $messagesSentAgg,
            ];
        }

        return response()->json($metrics);
    }
}
