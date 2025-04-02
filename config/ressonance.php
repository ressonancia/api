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

    'refresh_reverb_url' => env('REFRESH_REVERV_URL', 'http://localhost:8080/refresh-applications'),
];