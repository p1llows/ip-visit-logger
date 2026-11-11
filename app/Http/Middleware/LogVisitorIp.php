<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogVisitorIp
{
    public function handle(Request $request, Closure $next): Response
    {
        // Don't log people viewing the log itself.
        if (! $request->is('logs', 'up', 'favicon.ico', 'visits.json', 'api/browse')) {
            Log::channel('visits')->info('visit', [
                'ip' => $request->ip(),
                'path' => '/'.ltrim($request->path(), '/'),
                'url' => $request->fullUrl(),
                'referer' => $request->headers->get('referer'),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $next($request);
    }
}
