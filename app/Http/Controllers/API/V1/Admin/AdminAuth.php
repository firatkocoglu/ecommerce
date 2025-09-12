<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuth extends Controller
{
    public function login(Request $request)
    {
        // We use tokens for admin login
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::where('email', $data['email'])->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Create a Sanctum Personal Access Token for the admin
        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ],
        ], 200);
    }

    public function me(Request $request)
    {
        $admin = $request->user();

        return response()->json(['admin' => $admin], 200);
    }

    public function logout(Request $request)
    {
        $admin = $request->user();
        $admin->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout successful'], 200);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'All tokens revoked'], 200);
    }
}
