<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordChangeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    public function changePassword(PasswordChangeRequest $request): JsonResponse
    {

        $user = Auth::user();
        $user->password = bcrypt($request->password);
        $user->save();

        Log::info('Password Changed For: '.$user->email);

        return response()->json([
            'message' => 'Password has changed',
        ]);
    }
}
