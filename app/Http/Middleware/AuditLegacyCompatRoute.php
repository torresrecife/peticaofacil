<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuditLegacyCompatRoute
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('legacy.audit_legacy_routes', false)) {
            return $next($request);
        }

        $route = $request->route();
        $routeName = $route ? $route->getName() : null;
        $legacyFlags = [
            'compat_public_model_routes' => (bool) config('legacy.compat_public_model_routes', true),
            'compat_public_piece_editor_route' => (bool) config('legacy.compat_public_piece_editor_route', true),
            'compat_admin_sql_routes' => (bool) config('legacy.compat_admin_sql_routes', true),
            'compat_admin_model_routes' => (bool) config('legacy.compat_admin_model_routes', true),
        ];

        try {
            $response = $next($request);

            Log::info('legacy_route_hit', [
                'route_name' => $routeName,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
                'user_id' => optional($request->user())->id,
                'ip' => $request->ip(),
                'legacy_flags' => $legacyFlags,
            ]);

            return $response;
        } catch (HttpExceptionInterface $exception) {
            Log::warning('legacy_route_hit', [
                'route_name' => $routeName,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $exception->getStatusCode(),
                'user_id' => optional($request->user())->id,
                'ip' => $request->ip(),
                'legacy_flags' => $legacyFlags,
            ]);

            throw $exception;
        }
    }
}
