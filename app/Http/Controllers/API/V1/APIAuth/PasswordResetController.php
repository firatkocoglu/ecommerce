<?php

namespace App\Http\Controllers\API\V1\APIAuth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255']
        ]);

        $status = Password::broker('users')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['status' => 'success', 'message' => __('passwords.sent')], 200);
        }

        return response()->json(['error' => __($status), 'message' => 'Unable to send reset link'], 422);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        
        $status = Password::broker('users')->reset(
            $data,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );
        
        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['status' => 'success', 'message' => __('passwords.reset')], 200);
        }

        return response()->json(['error' => __($status), 'message' => 'Unable to reset password'], 422);
    }
}
