<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrganizationRequest;
use App\Jobs\RefreshReverb;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class OrganizationsController extends Controller
{
    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('update', $organization);

        $organization->update($request->validated());

        return response()->json($organization);
    }

    public function destroy(Organization $organization): Response
    {
        $this->authorize('delete', $organization);

        $organization->apps()->delete();
        $organization->delete();
        RefreshReverb::dispatch();

        return response()->noContent();
    }
}
