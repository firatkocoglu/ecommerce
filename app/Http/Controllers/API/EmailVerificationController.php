<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    public function status(Request $request){
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
                : redirect(rtrim(config('app.frontend_url', '/'), '/') . '/login')
                    ->with('status', 'Please log in to verify your email.');
        }
        
        if ($user?->hasVerifiedEmail()) {
            return $request->expectsJson() ? response()->json(['message' => 'Email is already verified.'], 200) : redirect(rtrim(config('app.frontend_url', '/'), '/'))->with('status', 'Email is already verified.');
        }

        $verificationRequest->fulfill();

        return $request->expectsJson() ? response()->json(['message' => 'Email verified successfully.'], 200) : redirect(rtrim(config('app.frontend_url', '/'), '/'))->with('status', 'Email verified successfully.');
    }
}