<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth extends Controller
{
    public function show()
    {
        return view('admin.admin-web-login');
    }

    public function login(Request $request)
    {
        Auth::shouldUse('admin');

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->withInput($request->only('email', 'remember'));
        }

        $request->session()->regenerate();

        return redirect()->intended('/admin/me');
    }

    public function me(Request $request)
    {
        $admin = $request->user('admin');

        return response()->json(['admin' => $admin], 200);
    }

    public function logout(Request $request)
    {
        Auth::shouldUse('admin');
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function logoutAll(Request $request)
    {
        Auth::shouldUse('admin');
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
