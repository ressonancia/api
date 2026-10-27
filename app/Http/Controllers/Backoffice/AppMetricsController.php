<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\Organization;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class AppMetricsController extends Controller
{
    public function index(): JsonResponse
    {
        $apps = App::with('organization.users')->get();
        $connectionResponses = [];

        foreach ($apps->chunk(10) as $appBatch) {
            $batchResponses = Http::pool(function (Pool $pool) use ($appBatch) {
                foreach ($appBatch as $app) {
                    $pool->as((string) $app->id)
                        ->connectTimeout(2)
                        ->timeout(5)
                        ->get($this->signedReverbRequestUrl($app));
                }
            });

            $connectionResponses += $batchResponses;
        }

        $metrics = [];

        foreach ($apps as $app) {
            $connections = 0;
            $connectionsResponse = $connectionResponses[$app->id] ?? null;

            if ($connectionsResponse instanceof Response && $connectionsResponse->successful()) {
                $connectionsData = $connectionsResponse->json();
                $connections = $connectionsData['connections'] ?? 0;
            }

            $owner = $app->organization ? $app->organization->users->where('pivot.role', Organization::ROLE_OWNER)->first() : null;

            $metrics[] = [
                'app_name' => $app->app_name,
                'app_id' => $app->app_id,
                'user_mail' => $owner ? $owner->email : ($app->organization && $app->organization->users->first() ? $app->organization->users->first()->email : null),
                'current_connections' => (int) $connections,
                'messages_sent' => 0,
            ];
        }

        return response()->json($metrics);
    }

    private function signedReverbRequestUrl(App $app): string
    {
        $path = "/apps/{$app->app_id}/connections";
        $method = 'GET';
        $params = [];
        $key = $app->app_key;
        $secret = $app->app_secret;
        $host = config('reverb.servers.reverb.host');
        $port = config('reverb.servers.reverb.port');
        $scheme = config('app.force_schema', false) ? 'https' : 'http';

        $params['auth_key'] = $key;
        $params['auth_timestamp'] = time();
        $params['auth_version'] = '1.0';

        ksort($params);

        $stringToSign = implode("\n", [
            strtoupper($method),
            $path,
            http_build_query($params),
        ]);

        $params['auth_signature'] = hash_hmac('sha256', $stringToSign, $secret);

        $baseUrl = rtrim(config('ressonance.reverb_api_url') ?: "{$scheme}://{$host}:{$port}", '/');

        return "{$baseUrl}{$path}?" . http_build_query($params);
    }
}
