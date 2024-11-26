<?php

use Illuminate\Auth\Middleware\Authenticate;

test('user needs to be logged in to retriev its own information', function () {
    $this->withMiddleware(Authenticate::class);

    $this->getJson(route('api.me'))->assertUnauthorized();
});