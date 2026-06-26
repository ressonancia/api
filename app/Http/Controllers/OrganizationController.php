<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Http\Requests\UpdateOrganizationUserRoleRequest;
use App\Models\App;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OrganizationUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OrganizationController extends Controller
{
    public function show(Organization $organization): JsonResponse
    {
        if (Auth::user()->cannot('view', $organization)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return response()->json(
            $organization->load('users')
        );
    }

    public function store(CreateOrganizationRequest $request): JsonResponse
    {
        $user = Auth::user();
        $maximumOrganizationsPerUser = config(
            'ressonance.max_organizations_per_user',
            20
        );

        throw_unless(
            is_int($maximumOrganizationsPerUser),
            RuntimeException::class,
            'Configuration "ressonance.max_organizations_per_user" must be an integer'
        );

        if ($user->organizations()->count() >= $maximumOrganizationsPerUser) {
            return response()->json([
                'message' => "The user cannot have more than {$maximumOrganizationsPerUser} organizations",
            ], Response::HTTP_PRECONDITION_FAILED);
        }

        $organization = Organization::create($request->validated());

        $user->organizations()->attach($organization->id, [
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

    public function updateUserRole(
        OrganizationUser $organizationUser,
        UpdateOrganizationUserRoleRequest $request
    ): JsonResponse {
        $organization = $organizationUser->organization()->firstOrFail();

        if (Auth::user()->cannot('edit', $organization)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($organizationUser->role === Organization::ROLE_OWNER) {
            return response()->json([
                'message' => 'The owner role cannot be changed.',
            ], Response::HTTP_PRECONDITION_FAILED);
        }

        $organizationUser->role = $request->validated('role');
        $organizationUser->save();

        return response()->json($organizationUser->refresh());
    }

    public function removeUserOrganization(OrganizationUser $organizationUser): Response|JsonResponse
    {
        $organization = $organizationUser->organization()->firstOrFail();

        if (Auth::user()->cannot('edit', $organization)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($organizationUser->user_id === Auth::id()) {
            return response()->json([
                'message' => 'The user cannot remove themselves from the organization.',
            ], Response::HTTP_PRECONDITION_FAILED);
        }

        if ($organizationUser->role === Organization::ROLE_OWNER) {
            return response()->json([
                'message' => 'The owner cannot be removed from the organization.',
            ], Response::HTTP_PRECONDITION_FAILED);
        }

        Invitation::where('organization_id', $organization->id)
            ->where('email', $organizationUser->user()->value('email'))
            ->delete();

        $organizationUser->delete();

        return response()->noContent();
    }
}
