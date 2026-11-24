<?php

use App\Models\App;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

test('user can fetch metrics only for apps in their organizations', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();

    $secondOrganization = Organization::factory()->create(['name' => 'Second Org']);
    $user->organizations()->attach($secondOrganization->id, [
        'role' => Organization::ROLE_MEMBER,
    ]);

    $appOne = App::factory()->create([
        'organization_id' => $organization->id,
        'app_name' => 'Primary App',
        'app_language_choice' => 'laravel',
    ]);

    $appTwo = App::factory()->create([
        'organization_id' => $secondOrganization->id,
        'app_name' => 'Secondary App',
        'app_language_choice' => 'node',
    ]);

    // Belongs to another organization — must not be returned
    $anotherUser = User::factory()->create();
    $otherApp = App::factory()->create([
        'organization_id' => $anotherUser->organizations()->first()->id,
        'app_name' => 'Other App',
    ]);

    Http::fake([
        "*/apps/{$appOne->app_id}/connections*" => Http::response(['connections' => 14], 200),
        "*/apps/{$appTwo->app_id}/connections*" => Http::response(['connections' => 3], 200),
        "*/apps/{$otherApp->app_id}/connections*" => Http::response(['connections' => 99], 200),
    ]);

    $response = $this->getJson(route('api.metrics.index'));

    $response->assertStatus(Response::HTTP_OK);
    $data = collect($response->json());

    expect($data)->toHaveCount(2);
    expect($data->pluck('app_id')->all())->not->toContain($otherApp->app_id);

    $firstMetric = $data->firstWhere('app_id', $appOne->app_id);
    expect($firstMetric)->toMatchArray([
        'id' => $appOne->id,
        'app_id' => $appOne->app_id,
        'app_name' => 'Primary App',
        'app_language_choice' => 'laravel',
        'organization_id' => $organization->id,
        'organization_name' => $organization->name,
        'current_connections' => 14,
    ]);

    $secondMetric = $data->firstWhere('app_id', $appTwo->app_id);
    expect($secondMetric)->toMatchArray([
        'id' => $appTwo->id,
        'app_id' => $appTwo->app_id,
        'app_name' => 'Secondary App',
        'organization_id' => $secondOrganization->id,
        'organization_name' => 'Second Org',
        'current_connections' => 3,
    ]);
});

test('metrics gracefully defaults to zero when reverb fails', function () {
    $user = $this->login();
    $organization = $user->organizations()->first();

    $app = App::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Http::fake([
        "*/apps/{$app->app_id}/connections*" => Http::response([], 500),
    ]);

    $response = $this->getJson(route('api.metrics.index'));

    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonFragment([
        'app_id' => $app->app_id,
        'current_connections' => 0,
    ]);
});

test('user gets empty list when organizations have no apps', function () {
    $this->login();

    Http::fake();

    $response = $this->getJson(route('api.metrics.index'));

    $response->assertStatus(Response::HTTP_OK);
    $response->assertExactJson([]);
    Http::assertNothingSent();
});

test('user needs to be logged in to view metrics', function () {
    $this->withMiddleware(Authenticate::class);

    $this->getJson(route('api.metrics.index'))->assertUnauthorized();
});

test('user needs to verify email to view metrics', function () {
    $this->withMiddleware(EnsureEmailIsVerified::class);

    $this->getJson(route('api.metrics.index'))->assertForbidden();
});

test('backoffice apps endpoint returns metrics for all apps', function () {
    $user = User::factory()->create(['email' => 'owner@example.com']);
    $organization = $user->organizations()->first();

    $app = App::factory()->create([
        'organization_id' => $organization->id,
        'app_name' => 'Backoffice Visible App',
    ]);

    Http::fake([
        "*/apps/{$app->app_id}/connections*" => Http::response(['connections' => 7], 200),
    ]);

    $response = $this->getJson(route('backoffice.apps.index'));

    $response->assertStatus(Response::HTTP_OK);
    $response->assertJsonFragment([
        'app_name' => 'Backoffice Visible App',
        'app_id' => $app->app_id,
        'user_mail' => 'owner@example.com',
        'current_connections' => 7,
        'messages_sent' => 0,
    ]);
});
