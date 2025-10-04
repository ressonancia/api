<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordResetRequest;
use App\Models\User;
use Closure;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function send(): JsonResponse
    {
        request()->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            request()->only('email')
        );

        if ($status === PasswordBroker::RESET_LINK_SENT) {
            Log::info('Password Reset Sent to: '.request()->email);
        } else {
            Log::info('Password Reset not Sent: '.$status);
        }

        return response()->json([
            'message' => 'Reset password sent',
        ]);
    }

    public function reset(PasswordResetRequest $request): JsonResponse
    {

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            Closure::fromCallable([$this, 'resetPassword'])
        );

        if ($status === PasswordBroker::PASSWORD_RESET) {
            Log::info('Password Reseted For: '.request()->email);

            return response()->json([
                'message' => 'Password has changed',
            ]);
        }

        Log::info('Password Reset Try With Invalid Token For: '.request()->email);

        return response()->json([
            'message' => 'Invalid token',
        ], Response::HTTP_BAD_REQUEST);
    }

    private function resetPassword(User $user, string $password): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
        ])->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));
    }
}
