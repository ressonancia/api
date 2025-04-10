<?php

return [
	/*
    |--------------------------------------------------------------------------
    | Refresh Reverb URL
    |--------------------------------------------------------------------------
    |
    | The webservice will call this url from ressonance websocket server
	| This URL refresh all websocket applications without restarting the server
	| Should be called when any application is updated, created or deleted
    |
    */

    'refresh_reverb_url' => env('REFRESH_REVERB_URL', 'http://localhost:8080/refresh-applications'),

	/*
    |--------------------------------------------------------------------------
    | Disable websocket integration
    |--------------------------------------------------------------------------
    |
    | Sometime for local environment you want to test
    | the api without keet the websockets running.
    | This config avoid requests to the websocket servers if disabled
    |
    */

    'websocket_integration' => env(
        'RESSONANCE_WEBSOCKET_INTEGRATION_ENABLED',
        true
    ),
];