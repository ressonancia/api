<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BackofficeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $apps = App::with('user')->paginate($perPage);

        $startOfMonthTimestamp = Carbon::now()->startOfMonth()->timestamp;

        $apps->through(function ($app) use ($startOfMonthTimestamp) {
            $currentConnections = DB::table('pulse_values')
                ->where('type', 'reverb_connections')
                ->where('key', $app->app_id)
                ->value('value');

            $messagesSentAgg = DB::table('pulse_aggregates')
                ->where('type', 'reverb_message')
                ->where('key', $app->app_id)
                ->where('bucket', '>=', $startOfMonthTimestamp)
                ->where('aggregate', 'count')
                ->sum('value');

            $messagesSentEntries = 0;
            if (!$messagesSentAgg) {
                $messagesSentEntries = DB::table('pulse_entries')
                    ->where('type', 'reverb_message')
                    ->where('key', $app->app_id)
                    ->where('timestamp', '>=', $startOfMonthTimestamp)
                    ->count();
            }

            return [
                'app_name' => $app->app_name,
                'app_app' => $app->app_id,
                'app_language' => $app->app_language_choice,
                'user_email' => $app->user->email ?? null,
                'app_current_connections' => (int) $currentConnections,
                'messages_sent_at_month' => (int) ($messagesSentAgg ?: $messagesSentEntries),
            ];
        });

        return response()->json($apps);
    }
}
