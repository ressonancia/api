<?php

pest()->extend(Tests\TestCase::class);

it('application force schema defaults to false', function () {
    expect(url('force-schema'))
        ->toBe('http://localhost/force-schema');
});

it('application can force schema', function () {

    putenv('APP_FORCE_SCHEMA=true');
    $this->refreshApplication();

    expect(url('force-schema'))
        ->toBe('https://localhost/force-schema');
});
