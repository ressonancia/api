<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\Organization;
use App\Services\AppMetricsService;
use Illuminate\Http\JsonResponse;

class AppMetricsController extends Controller
{
    public function __construct(
        private AppMetricsService $appMetricsService,
    ) {}

    public function index(): JsonResponse
    {
        $apps = App::with('organization.users')->get();
        $connectionsByApp = $this->appMetricsService->getCurrentConnections($apps);

        $metrics = [];

        foreach ($apps as $app) {
            $owner = $app->organization
                ? $app->organization->users->where('pivot.role', Organization::ROLE_OWNER)->first()
                : null;

            $metrics[] = [
                'app_name' => $app->app_name,
                'app_id' => $app->app_id,
                'user_mail' => $owner ? $owner->email : ($app->organization && $app->organization->users->first() ? $app->organization->users->first()->email : null),
                'current_connections' => $connectionsByApp[$app->id] ?? 0,
                'messages_sent' => 0,
            ];
        }

        return response()->json($metrics);
    }
}
