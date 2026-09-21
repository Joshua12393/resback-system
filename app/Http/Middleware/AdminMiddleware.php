<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * Restrict access to administrative and faculty users only.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        if (! in_array($request->user()->role, ['super_admin', 'admin', 'faculty'], true)) {
            abort(403, 'Access denied. This area is restricted to administrators and faculty only.');
        }

        return $next($request);
    }
}
