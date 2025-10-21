<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function signup(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        // Search user by username
        $user = User::where('username', $request->username)->first();

        // Check if user not found, create new user
        if (!$user) {
            $user = User::create([
                'username' => $request->username,
                'password' => Hash::make($request->password),
            ]);
            $message = 'Akun baru dibuat & login berhasil';
        } else {
            // If user found, check password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Password salah'], 401);
            }
            $message = 'Login berhasil';
        }

        // Generate token
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        return response()->json([
            'message' => $message,
            'user' => $user,
            'token' => $token
        ]);
    }

    // Get user
    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }
    }

    // Logout
    public function logout()
    {
        try {
            $token = JWTAuth::getToken();

            if ($token) {
                JWTAuth::invalidate($token);
                return response()->json(['message' => 'Logout berhasil']);
            } else {
                return response()->json(['error' => 'Token tidak ditemukan'], 400);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Gagal logout: ' . $e->getMessage()], 500);
        }
    }
}