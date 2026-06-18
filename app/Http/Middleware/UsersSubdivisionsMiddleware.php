<?php

namespace App\Http\Middleware;

use App\Services\Response\ResponseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersSubdivisionsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (! $request->has('subdivision_id')) {
            return $next($request);
        }

        $user = $request->user();
        $subdivisionId = $request->input('subdivision_id');

        if (! $user || ! $user->hasSubdivisionAccess($subdivisionId)) {
            return ResponseService::sendJsonResponse(false, 403, [
                'subdivision_id' => ['Forbidden'],
            ]);
        }

        return $next($request);
    }
}
