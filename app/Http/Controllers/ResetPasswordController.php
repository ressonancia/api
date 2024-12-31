<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ResetPasswordController extends Controller
{
    public function send() : JsonResponse {
        request()->validate(['email' => 'required|email']);
 
        $status = Password::sendResetLink(
            request()->only('email')
        );
     
        return response()->json([
            'message' => 'Reset password sent'
        ]);
    }
}
