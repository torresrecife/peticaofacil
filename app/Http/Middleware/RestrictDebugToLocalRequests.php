<?php

namespace App\Http\Middleware;

use App\Support\DebugAccess;
use Closure;

class RestrictDebugToLocalRequests
{
    /**
     * Roda antes de tudo: as rotas /_ignition e as respostas de erro em JSON
     * consultam app.debug durante a requisicao.
     */
    public function handle($request, Closure $next)
    {
        DebugAccess::restrict($request);

        return $next($request);
    }
}
