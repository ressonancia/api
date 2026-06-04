<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\App;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrganizationController extends Controller
{
    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $organization = Organization::create($request->validated());

        Auth::user()->organizations()->attach($organization->id, [
            'role' => Organization::ROLE_OWNER,
        ]);

        return response()->json($organization, Response::HTTP_CREATED);
    }

    public function destroy(Organization $organization): Response|JsonResponse
    {
        $user = Auth::user();
        $user->load('organizations');

        $userOwnedOrganizations = $user->organizations
            ->where('pivot.role', Organization::ROLE_OWNER);

        if (! $userOwnedOrganizations->contains($organization)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($userOwnedOrganizations->count() <= 1) {
            return response()->json([
                'message' => 'The user cannot delete the last owned organization',
            ], Response::HTTP_PRECONDITION_FAILED);
        }

        Log::info('Organization deleted', [
            'organization_id' => $organization->id,
            'user_id' => $user->id,
        ]);

        App::where('organization_id', $organization->id)->delete();
        $organization->users()->detach();
        $organization->delete();

        return response()->noContent();
    }

    public function update(Organization $organization, UpdateOrganizationRequest $request): JsonResponse
    {
        if (Auth::user()->cannot('edit', $organization)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $organization->update($request->validated());

        return response()->json($organization->refresh());
    }
}
