<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;

class EmailVerificationController extends Controller
{
    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        $request->fulfill();

        return response()->json([
            'data' => [
                'verified' => true,
            ],
        ]);
    }

    public function send(): JsonResponse
    {
        request()->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent!',
        ]);
    }
}
