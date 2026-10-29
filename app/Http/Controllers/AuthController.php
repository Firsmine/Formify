<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'string|required',
            'email' => 'required|email|unique:users,email',
            'password' => 'string|required|min:6'
        ]);
        $user = User::create($data);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Register successful',
            'user' => [$user, 'token' => $token]
        ], 201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'string|required',
            'password' => 'string|required|min:5'
        ]);
        $user = User::where('email', $request->email)->first();
        $user->tokens()->delete();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email or password incorrect'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login success',
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'accessToken' => $token
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'message' => 'Logout success'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }
    }
}
