<?php

use App\Models\Invitation;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\OrganizationInvitationNotification;

pest()->extend(Tests\TestCase::class);

it('builds the mail notification correctly', function () {
    $expiresAt = now()->addDay();
    $organization = Organization::factory()->make(['name' => 'Acme Corp']);
    $inviter = User::factory()->make(['name' => 'John Doe']);
    $invitation = Invitation::factory()->make(['expires_at' => $expiresAt]);
    $url = 'https://example.com/invite/abc123';

    $notification = new OrganizationInvitationNotification($organization, $inviter, $invitation, $url);
    $mail = $notification->toMail(new stdClass);

    expect($notification->via(new stdClass))->toBe(['mail'])
        ->and($mail->subject)->toBe(config('app.name').'::Organization Invitation')
        ->and($mail->greeting)->toBe('You were invited to join an organization')
        ->and($mail->introLines)->toContain('John Doe invited you to join Acme Corp.')
        ->and($mail->introLines)->toContain('This invitation link expires at '.$expiresAt->format('Y-m-d H:i:s').'.')
        ->and($mail->actionText)->toBe('Accept Invitation')
        ->and($mail->actionUrl)->toBe($url);
});
