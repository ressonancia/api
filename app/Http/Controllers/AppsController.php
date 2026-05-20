<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAppRequest;
use App\Jobs\RefreshReverb;
use App\Models\App;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AppsController extends Controller
{
    public function index(Organization $organization): JsonResponse
    {
        $this->authorize('view', $organization);

        return response()->json(
            $organization->apps()->paginate(1000)
        );
    }

    public function show(Organization $organization, App $app): JsonResponse
    {
        $this->authorize('view', $organization);
        $this->authorize('view', $app);

        return response()->json($app);
    }

    public function store(Organization $organization, CreateAppRequest $request, Str $stringSupport): JsonResponse
    {
        $this->authorize('create', [App::class, $organization]);

        $createdApp = App::create([
            'user_id' => Auth::user()->id,
            'organization_id' => $organization->id,
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

    public function destroy(Organization $organization, App $app): Response
    {
        $this->authorize('view', $organization);
        $this->authorize('delete', $app);

        $app->delete();
        RefreshReverb::dispatch();

        return response()->noContent();
    }
}
