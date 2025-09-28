<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectIfNotAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! auth('admin_tools')->check()) {
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
