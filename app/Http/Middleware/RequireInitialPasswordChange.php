<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequireInitialPasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        if (in_array(optional($request->route())->getName(), ['logout', 'password.force', 'password.force.update'], true)) {
            return $next($request);
        }

        if (Auth::user()->requiresInitialPasswordChange()) {
            return redirect()->route('password.force');
        }

        return $next($request);
    }
}
