<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use  App\User;

class AuthController extends Controller
{
    /**
     * Store a new user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function register(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed',
        ]);

        try {

            $user = new User;
            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $plainPassword = $request->input('password');
            $user->password = app('hash')->make($plainPassword);

            $user->save();

            //return successful response
            return response()->json(['user' => $user, 'message' => 'CREATED'], 201);

        } catch (\Exception $e) {
            //return error message
            return response()->json(['message' => 'User Registration Failed!'], 409);
        }
    }

    public function login(Request $request)
    {
        //validate incoming request 
        $this->validate($request, [
            'email' => 'sometimes|string',
            'username' => 'sometimes|string',
            'password' => 'required|string',
        ]);

        if($request->email){
            $credentials = [ 
                "password" => $request->password, 
                "email" => $request->email
            ];
        }

        if($request->username){
            $credentials = [ 
                "password" => $request->password, 
                "name" => $request->username, 
            ];
        }


        if (! $token = Auth::attempt($credentials)) {
            return response()->json([
                "diagnostic" => [
                    'code' => 401,
                    'message' => 'Unauthorized'
                ]
            ], 200);
        }

        return $this->respondWithToken($token);
    }
}