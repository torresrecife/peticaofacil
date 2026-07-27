<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureLegacyRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (empty($roles)) {
            $roles = ['ADM', 'GER'];
        }

        if (!in_array($user->nivel_usu, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
