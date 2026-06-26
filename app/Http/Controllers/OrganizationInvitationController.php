<?php

namespace App\Http\Controllers;

use App\Http\Requests\InviteOrganizationMemberRequest;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use RuntimeException;

class OrganizationInvitationController extends Controller
{
    public function store(Organization $organization, InviteOrganizationMemberRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        if ($user->cannot('edit', $organization)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $inviteHours = config('ressonance.organization_invitation_expiration_hours', 48);

        throw_unless(
            is_int($inviteHours),
            new RuntimeException(
                'Invalid organization invitation expiration hours. Should be an integer'
            )
        );

        if ($organization->users()->where('email', $validated['email'])->exists()) {
            abort(Response::HTTP_PRECONDITION_FAILED);
        }

        $invitations = Invitation::where('organization_id', $organization->id)
            ->where('email', $validated['email'])
            ->get();

        // check if the user haved accepted the same invite
        if ($invitations->whereNotNull('joined_at')->count()) {
            abort(Response::HTTP_PRECONDITION_FAILED);
        }

        $invitations->each->delete();

        $invitation = Invitation::create([
            'organization_id' => $organization->id,
            'inviter_id' => $user->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'expires_at' => now()->addHours($inviteHours),
        ]);

        $invitationUrl = URL::temporarySignedRoute(
            'api.invitations.accept',
            now()->addHours($inviteHours),
            ['invitation' => $invitation->id]
        );

        $spaInvitationUrl = config('app.spa_url').'/email-invitation?'
                .http_build_query([
                    'route' => rtrim(strtr(base64_encode($invitationUrl), '+/', '-_'), '='),
                ]);

        Notification::route('mail', $invitation->email)
            ->notify(new OrganizationInvitationNotification(
                $organization,
                $user,
                $invitation,
                $spaInvitationUrl,
            ));

        return response()->json($invitation->fresh(), Response::HTTP_CREATED);
    }

    public function accept(Invitation $invitation): JsonResponse
    {

        if ($invitation->expires_at->isPast()) {
            abort(Response::HTTP_PRECONDITION_FAILED);
        }

        if (! is_null($invitation->joined_at)) {
            abort(Response::HTTP_PRECONDITION_FAILED);
        }

        if (! $invitation->organization()->exists() || ! $invitation->inviter()->exists()) {
            abort(Response::HTTP_PRECONDITION_FAILED);
        }

        $invitation->joined_at = now();
        $invitation->save();

        $user = User::withoutOrganizationCreation()->firstOrCreate([
            'email' => $invitation->email,
        ], [
            'name' => $invitation->name,
            'email_verified_at' => now(),
        ]);

        $user->organizations()->syncWithoutDetaching([
            $invitation->organization_id => [
                'role' => $invitation->role,
            ],
        ]);

        $token = $user->createToken('From Invitation');

        return response()->json([
            'data' => [
                'invited_organization_id' => $invitation->organization_id,
                'user' => $user,
                'access_token' => $token->accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $token->token
                    ->expires_at->diffInSeconds(now(), true),
            ],
        ]);
    }
}
