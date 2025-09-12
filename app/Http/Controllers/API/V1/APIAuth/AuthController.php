<?php

namespace App\Http\Controllers\API\V1\APIAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $user = User::create([
                'firstname' => $data['firstname'],
                'lastname' => $data['lastname'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'customer',
            ]);

            $user->sendEmailVerificationNotification();

            Auth::login($user);
            $request->session()->regenerate();

            return response()->json(['message' => 'Registration successful', 'user' => $user], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'User creation failed', 'error' => app()->environment('local') ? $e->getMessage() : null], 500);
        }
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($data, true)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $request->session()->regenerate();

        return response()->json(['message' => 'Login successful', 'user' => $request->user()], 200);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()], 200);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $response = response()->json(['message' => 'Logout successful'], 200);

        return $response->withCookie(cookie()->forget(config('session.cookie')))->withCookie(cookie()->forget('XSRF-TOKEN'));
    }
}
