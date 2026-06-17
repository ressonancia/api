<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AppMetricsController extends Controller
{
    public function index(): JsonResponse
    {
        $apps = App::with('user')->get();

        $metrics = [];
        $startOfMonthTimestamp = Carbon::now()->startOfMonth()->getTimestamp();

        $appIds = $apps->pluck('app_id')->toArray();

        $allConnections = DB::table('pulse_values')
            ->where('type', 'reverb_connections')
            ->whereIn('key', $appIds)
            ->pluck('value', 'key');

        $allMessages = DB::table('pulse_aggregates')
            ->where('type', 'reverb_message')
            ->whereIn('key', $appIds)
            ->where('bucket', '>=', $startOfMonthTimestamp)
            ->where('aggregate', 'count')
            ->groupBy('key')
            ->selectRaw('\'key\', sum(value) as total')
            ->pluck('total', 'key');

        foreach ($apps as $app) {
            $metrics[] = [
                'app_name' => $app->app_name,
                'app_id' => $app->app_id,
                'user_mail' => $app->user ? $app->user->email : null,
                'current_connections' => (int) ($allConnections[$app->app_id] ?? 0),
                'messages_sent' => (int) ($allMessages[$app->app_id] ?? 0),
            ];
        }

        return response()->json($metrics);
    }
}
