<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAppRequest;
use App\Jobs\RefreshReverb;
use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AppsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Auth::user()->apps()->paginate(1000)
        );
    }

    public function show(App $app): JsonResponse
    {

        if (Auth::user()->cannot('view', $app)) {
            abort(403);
        }

        return response()->json($app);
    }

    public function store(CreateAppRequest $request, Str $stringSupport): JsonResponse
    {
        $createdApp = App::create([
            'user_id' => Auth::user()->id,
            'app_name' => $request->get('app_name'),
            'app_language_choice' => $request->get('app_language_choice'),
            'app_id' => (string) random_int(1000000000, 9999999999),
            'app_key' => $stringSupport->lower(
                $stringSupport->random(20)
            ),
            'app_secret' => $stringSupport->lower(
                $stringSupport->random(20)
            ),
        ]);

        RefreshReverb::dispatch();

        return response()->json(
            $createdApp,
            Response::HTTP_CREATED
        );
    }

    public function destroy(App $app): Response
    {
        if (Auth::user()->cannot('delete', $app)) {
            abort(403);
        }

        $app->delete();
        RefreshReverb::dispatch();

        return response()->noContent();
    }
}
