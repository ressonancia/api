<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    dd(\DB::table('pulse_aggregates')
            ->where('type', 'reverb_message')
            ->whereIn('key', [])
            ->where('bucket', '>=', '')
            ->where('aggregate', 'count')
            ->groupBy('key')
            ->selectRaw('key, sum(value) as total')->toSql());
});
