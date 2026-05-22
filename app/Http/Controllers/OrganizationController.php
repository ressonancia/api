<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class OrganizationController extends Controller
{
    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $organization = Organization::create($request->validated());

        Auth::user()->organizations()->attach($organization->id, [
            'id' => (string) Str::uuid(),
            'role' => Organization::ROLE_OWNER,
        ]);

        return response()->json($organization, Response::HTTP_CREATED);
    }
}
