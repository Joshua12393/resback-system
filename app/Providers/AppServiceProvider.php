<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination.default');
        Paginator::defaultSimpleView('pagination.simple');

        // Database sessions without the user_id / ip_address columns, so session rows
        // cannot be matched to feedback timestamps. Pair with SESSION_ENCRYPT=true.
        Session::extend('database_private', fn ($app) => new DatabaseSessionHandler(
            $app['db']->connection(config('session.connection')),
            config('session.table'),
            config('session.lifetime'),
        ));

        // Applies wherever Password::defaults() is used (registration, password changes).
        Password::defaults(fn () => Password::min(8)->letters()->mixedCase()->numbers());

        // Per-email limits stop password guessing; the looser per-IP limit
        // avoids locking out a whole campus network sharing one address.
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)
                ->by(Str::lower((string) $request->input('email')).'|'.$request->ip())
                ->response(fn (Request $request, array $headers) => $this->throttledResponse($headers, 'email')),
            Limit::perMinute(30)
                ->by('login-ip|'.$request->ip())
                ->response(fn (Request $request, array $headers) => $this->throttledResponse($headers, 'email')),
        ]);

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(10)
            ->by('register-ip|'.$request->ip())
            ->response(fn (Request $request, array $headers) => $this->throttledResponse($headers, 'email')));
    }

    /**
     * Send the user back to the form with a readable wait message.
     */
    private function throttledResponse(array $headers, string $field): RedirectResponse
    {
        $seconds = (int) ($headers['Retry-After'] ?? 60);

        return back()
            ->withErrors([$field => "Too many attempts. Please try again in {$seconds} seconds."])
            ->withInput(request()->except('password', 'password_confirmation'));
    }
}
