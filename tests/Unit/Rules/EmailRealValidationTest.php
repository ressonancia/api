<?php

use App\Rules\EmailRealValidation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use SendKit\Exceptions\SendKitException;
use SendKit\Laravel\Facades\SendKit;

pest()->extend(Tests\TestCase::class);

it('skips sendkit validation when default mailer is not sendkit', function () {
    config([
        'mail.default' => 'array',
        'app.enable_email_enhanced_verification' => true,
    ]);

    SendKit::shouldReceive('validateEmail')->never();

    $validator = Validator::make(
        ['email' => 'user@example.com'],
        ['email' => [new EmailRealValidation]]
    );

    expect($validator->passes())->toBeTrue();
});

it('skips sendkit validation when enhanced verification is disabled', function () {
    config([
        'mail.default' => 'sendkit',
        'app.enable_email_enhanced_verification' => false,
    ]);

    SendKit::shouldReceive('validateEmail')->never();

    $validator = Validator::make(
        ['email' => 'user@example.com'],
        ['email' => [new EmailRealValidation]]
    );

    expect($validator->passes())->toBeTrue();
});

it('passes when sendkit marks email as allowed', function () {
    config([
        'mail.default' => 'sendkit',
        'app.enable_email_enhanced_verification' => true,
    ]);

    SendKit::shouldReceive('validateEmail')
        ->once()
        ->with('user@example.com')
        ->andReturn([
            'should_block' => false,
        ]);

    $validator = Validator::make(
        ['email' => 'user@example.com'],
        ['email' => [new EmailRealValidation]]
    );

    expect($validator->passes())->toBeTrue();
});

it('fails and logs when sendkit marks email as blocked', function () {
    config([
        'mail.default' => 'sendkit',
        'app.enable_email_enhanced_verification' => true,
    ]);

    SendKit::shouldReceive('validateEmail')
        ->once()
        ->with('blocked@example.com')
        ->andReturn([
            'should_block' => true,
            'reason' => 'disposable_domain',
        ]);

    Log::shouldReceive('info')
        ->once()
        ->with('Email verification failed', [
            'email' => 'blocked@example.com',
            'result' => [
                'should_block' => true,
                'reason' => 'disposable_domain',
            ],
        ]);

    $validator = Validator::make(
        ['email' => 'blocked@example.com'],
        ['email' => [new EmailRealValidation]]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->first('email'))
        ->toBe('The email field must be a valid email address.');
});

it('logs and ignores validation when sendkit throws an exception', function () {
    config([
        'mail.default' => 'sendkit',
        'app.enable_email_enhanced_verification' => true,
    ]);

    SendKit::shouldReceive('validateEmail')
        ->once()
        ->with('error@example.com')
        ->andThrow(new SendKitException('sendkit unavailable', 500));

    Log::shouldReceive('info')
        ->once()
        ->with('Email verification failed', [
            'email' => 'error@example.com',
            'error' => 'sendkit unavailable',
        ]);

    $validator = Validator::make(
        ['email' => 'error@example.com'],
        ['email' => [new EmailRealValidation]]
    );

    expect($validator->passes())->toBeTrue();
});
