<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteOrganizationMemberRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class OrganizationInvitationsController extends Controller
{
    public function store(InviteOrganizationMemberRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('invite', $organization);

        $invitation = OrganizationInvitation::create([
            'organization_id' => $organization->id,
            'invited_by_user_id' => Auth::id(),
            'email' => strtolower($request->validated('email')),
            'role' => $request->validated('role'),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new OrganizationInvitationNotification($invitation));

        return response()->json($invitation, Response::HTTP_CREATED);
    }

    public function accept(OrganizationInvitation $invitation): Response
    {
        $this->authorize('accept', $invitation);

        if ($invitation->accepted_at) {
            return response()->noContent();
        }

        $user = Auth::user();
        $organization = $invitation->organization;

        $existingMembership = $organization->users()
            ->where('users.id', $user->id)
            ->first();

        if (! $existingMembership) {
            $organization->users()->attach($user->id, [
                'role' => $invitation->role,
            ]);
        } elseif ($existingMembership->pivot->role !== Organization::ROLE_OWNER) {
            $organization->users()->updateExistingPivot($user->id, [
                'role' => $invitation->role,
            ]);
        }

        if (! $user->email_verified_at) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        $invitation->update([
            'accepted_at' => now(),
        ]);

        return response()->noContent();
    }
}
