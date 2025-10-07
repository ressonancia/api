<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccountRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    public function store(CreateAccountRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        event(new Registered($user));

        $token = $user->createToken('Pending Validation');

        return response()->json(
            [
                'user' => $user,
                'access_token' => $token->accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $token->token
                    ->expires_at->diffInSeconds(now()),
            ],
            Response::HTTP_CREATED
        );
    }

    public function destroy(): JsonResponse
    {

        $user = Auth::user();

        if ($user->apps()->count()) {
            return response()->json([
                'message' => 'The user should delete all apps before deleting the account',
            ], Response::HTTP_PRECONDITION_FAILED);
        }

        $user->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
