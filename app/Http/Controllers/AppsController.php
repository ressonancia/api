<?php

namespace App\Http\Controllers;

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

    function store(Str $stringSupport) : JsonResponse {
        return response()->json(
            App::create([
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
