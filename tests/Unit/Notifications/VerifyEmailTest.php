<?php

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

pest()->extend(TestCase::class);

it('verifies the email verification mail customization', function () {
    // Arrange: Set up a user and a custom mail handler
    $user = User::factory()->unverified()->create();
    $url = buildVerificationUrl($user);
    Notification::fake();

    $user->notify(new VerifyEmail);

    Notification::assertSentTo($user, VerifyEmail::class, function ($notification) use ($user, $url) {
        $mailMessage = $notification->toMail($user);

        expect($mailMessage->subject)->toBe(config('app.name') . '::Verify Email Address');
        expect($mailMessage->greeting)->toBe('THANKS FOR SIGNING UP!');
        expect($mailMessage->introLines)->toContain('Verify your E-mail Address clicking at the button bellow.');
        expect($mailMessage->actionText)->toBe('Verify Email Address');
        expect($mailMessage->actionUrl)->toBe($url);
        expect($mailMessage->outroLines)->toContain('If you did not create an account, no further action is required.');

        return true;
    });
});

function buildVerificationUrl(User $user): string
{
    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(Config::get('auth.verification.expire', 60)),
        [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]
    );

    return config('app.spa_url') . '/email-verification?'
        . http_build_query(['route' => urlencode(base64_encode($url))]);
}