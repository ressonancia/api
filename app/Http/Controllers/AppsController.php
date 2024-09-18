<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAppRequest;
use App\Models\App;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AppsController extends Controller
{
    function index() : JsonResponse {
        return response()->json(
            App::paginate()
        );
    }

    function store(CreateAppRequest $request, Str $stringSupport) : JsonResponse {
        return response()->json(
            App::create([
                'app_name' => $request->get('app_name'),
                'app_language_choice' => $request->get('app_language_choice'),
                'app_id' => (string) $stringSupport->uuid(),
                'app_key' => $stringSupport->lower(
                    $stringSupport->random(20)
                ),
                'app_secret' => $stringSupport->lower(
                    $stringSupport->random(20)
                ),
            ]),
            Response::HTTP_CREATED
        );
    }
}
