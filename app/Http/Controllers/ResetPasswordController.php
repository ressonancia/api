<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordResetRequest;
use App\Models\User;
use Closure;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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

    public function reset(PasswordResetRequest $request) : JsonResponse {
     
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            Closure::fromCallable([$this, 'resetPassword'])
        );
        
        return response()->json([
            'message' => 'Password has changed'
        ]);
    }

    private function resetPassword(User $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password)
        ])->setRememberToken(Str::random(60));
    
        $user->save();
    
        event(new PasswordReset($user));
    }
}
