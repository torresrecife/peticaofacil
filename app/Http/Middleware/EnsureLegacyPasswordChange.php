<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureLegacyPasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $routeName = optional($request->route())->getName();
        $allowedRoutes = [
            'logout',
            'legacy.logout.file',
            'password.force',
            'password.force.update',
        ];

        if (in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        if (Auth::user()->requiresInitialPasswordChange()) {
            return redirect()->route('password.force');
        }

        return $next($request);
    }
}
