<?php

namespace App\Services;

use App\Models\App;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AppMetricsService
{
    /**
     * Fetch live current connections per app ID (keyed by $app->id) from Reverb's management API.
     *
     * @param  Collection<int, App>  $apps
     * @return array<int|string, int>
     */
    public function getCurrentConnections(Collection $apps): array
    {
        if ($apps->isEmpty() || ! config('ressonance.websocket_integration', true)) {
            return [];
        }

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

        $connectionsByApp = [];

        foreach ($apps as $app) {
            $connections = 0;
            $connectionsResponse = $connectionResponses[(string) $app->id] ?? $connectionResponses[$app->id] ?? null;

            if ($connectionsResponse instanceof Response && $connectionsResponse->successful()) {
                $connectionsData = $connectionsResponse->json();
                $connections = $connectionsData['connections'] ?? 0;
            }

            $connectionsByApp[$app->id] = (int) $connections;
        }

        return $connectionsByApp;
    }

    public function signedReverbRequestUrl(App $app): string
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

        return "{$baseUrl}{$path}?".http_build_query($params);
    }
}
