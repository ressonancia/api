<?php

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
