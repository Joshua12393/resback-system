<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeedbackSubmitterMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            if ($request->isMethod('GET')) {
                return redirect()
                    ->route('dashboard')
                    ->with('error', 'Administrators cannot submit feedback.');
            }

            abort(403, 'Administrators cannot submit feedback.');
        }

        return $next($request);
    }
}
