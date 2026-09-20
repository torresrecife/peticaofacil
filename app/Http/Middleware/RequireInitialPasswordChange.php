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

        $user = Auth::user();
        if ($user->status_usu !== 'ATI'
            || ($user->temporary_password_expires_at && $user->temporary_password_expires_at->isPast())) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['username' => 'Acesso indisponivel ou expirado. Solicite uma nova senha temporaria ao responsavel.']);
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
