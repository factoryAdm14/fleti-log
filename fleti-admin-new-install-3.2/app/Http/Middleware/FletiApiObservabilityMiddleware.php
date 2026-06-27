<?php

namespace App\Http\Middleware;

use App\Lib\FletiObservability;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FletiApiObservabilityMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('api/*') && $response->getStatusCode() >= 400) {
            FletiObservability::apiError('http_error', [
                'status' => $response->getStatusCode(),
                'user_id' => optional($request->user('api'))->id,
                'user_type' => optional($request->user('api'))->user_type,
            ], $response->getStatusCode() >= 500 ? 'error' : 'warning');
        }

        return $response;
    }
}
