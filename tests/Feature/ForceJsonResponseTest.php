<?php

test('api just retrieve json', function () {
    // Make a GET request to the test route
    $response = $this->get('any route');

    // Assert the response content type is application/json
    $response->assertHeader('Content-Type', 'application/json');
});
