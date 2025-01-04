<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function logout() : Response {
        $user = Auth::user();
        $user->tokens->each->revoke();
        return response()->noContent();
    }
}
