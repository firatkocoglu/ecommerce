<?php

namespace App\Http\Controllers\Auth\APIAuth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function status(Request $request)
    {
        $verified = $request->user()?->hasVerifiedEmail() ?? false;

        return response()->json(['verified' => $verified], 200);
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified.'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent successfully.'], 200);
    }

    public function verify(EmailVerificationRequest $verificationRequest, Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated'], 401)
                : redirect(rtrim(config('app.frontend_url', '/'), '/').'/login')
                    ->with('status', 'Please log in to verify your email.');
        }

        if ($user?->hasVerifiedEmail()) {
            return $request->expectsJson() ? response()->json(['message' => 'Email is already verified.'], 200) : redirect(rtrim(config('app.frontend_url', '/'), '/'))->with('status', 'Email is already verified.');
        }

        // Fulfill the email verification request, $verificationRequest is an instance of EmailVerificationRequest, so it has access to fulfill method
        $verificationRequest->fulfill();

        return $request->expectsJson() ? response()->json(['message' => 'Email verified successfully.'], 200) : redirect(rtrim(config('app.frontend_url', '/'), '/'))->with('status', 'Email verified successfully.');
    }
}
