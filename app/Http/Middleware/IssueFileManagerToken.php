<?php

namespace App\Http\Middleware;

use App\Support\FileManagerToken;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

/**
 * Mantem o cookie que libera o CKFinder em dia com a sessao: emite e
 * renova para quem esta logado e ativo, e apaga para todos os demais
 * (visitante, logout, conta inativa, senha temporaria pendente).
 */
class IssueFileManagerToken
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (!is_object($response) || !isset($response->headers)) {
            return $response;
        }

        $user = Auth::user();
        $token = '';

        if ($user && $user->status_usu === 'ATI' && !$user->requiresInitialPasswordChange()) {
            $minutes = max(1, (int) config('session.lifetime', 120));
            $token = FileManagerToken::issue(
                $user->getAuthIdentifier(),
                time() + ($minutes * 60),
                (string) config('app.key')
            );
        }

        if ($token !== '') {
            $response->headers->setCookie(cookie(
                FileManagerToken::COOKIE,
                $token,
                $minutes,
                config('session.path', '/'),
                config('session.domain'),
                (bool) config('session.secure', false),
                true,
                false,
                'lax'
            ));
        } elseif ($request->cookies->has(FileManagerToken::COOKIE)) {
            $response->headers->setCookie(Cookie::forget(
                FileManagerToken::COOKIE,
                config('session.path', '/'),
                config('session.domain')
            ));
        }

        return $response;
    }
}
