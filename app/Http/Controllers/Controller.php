<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Laravel\Lumen\Routing\Controller as BaseController;
use App\Http\Resources\User;

class Controller extends BaseController
{
    protected function respondWithToken($token)
    {
        return response()->json([
            "response" => [
                "data" => [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => Auth::factory()->getTTL() * 60,
                    'user' => new User(Auth::user())
                ],
            ],
            "diagnostic" => [
                'code' => 200,
                'message' => "success"
            ]
        ], 200);
    }
}
