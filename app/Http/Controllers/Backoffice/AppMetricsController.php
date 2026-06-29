<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class AppMetricsController extends Controller
{
    public function index(): JsonResponse
    {
        $apps = App::with('user')->get();
        $metrics = [];

        foreach ($apps as $app) {
            $connections = 0;

            try {
                $connectionsResponse = $this->signedReverbRequest($app);
                if ($connectionsResponse->successful()) {
                    $connectionsData = $connectionsResponse->json();
                    $connections = $connectionsData['connections'] ?? 0;
                }
            } catch (\Exception $e) {}

            $metrics[] = [
                'app_name' => $app->app_name,
                'app_id' => $app->app_id,
                'user_mail' => $app->user ? $app->user->email : null,
                'current_connections' => (int) $connections,
                'messages_sent' => 0,
            ];
        }

        return response()->json($metrics);
    }

    private function signedReverbRequest(App $app): Response
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

        $url = "{$scheme}://{$host}:{$port}{$path}?" . http_build_query($params);

        return Http::get($url);
    }
}
