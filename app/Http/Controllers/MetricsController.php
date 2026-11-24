<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Services\AppMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MetricsController extends Controller
{
    public function __construct(
        private AppMetricsService $appMetricsService,
    ) {}

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $organizationIds = $user->organizations()->pluck('organizations.id');

        $apps = App::with('organization')
            ->whereIn('organization_id', $organizationIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $connectionsByApp = $this->appMetricsService->getCurrentConnections($apps);

        $metrics = [];

        foreach ($apps as $app) {
            $metrics[] = [
                'id' => $app->id,
                'app_id' => $app->app_id,
                'app_name' => $app->app_name,
                'app_language_choice' => $app->app_language_choice,
                'organization_id' => $app->organization_id,
                'organization_name' => $app->organization?->name,
                'current_connections' => $connectionsByApp[$app->id] ?? 0,
            ];
        }

        return response()->json($metrics);
    }
}
