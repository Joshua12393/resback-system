@extends('layouts.auth')
@section('title', 'Sign In')

@section('content')
<div class="auth-layout">
    <div class="auth-card">
        <span class="auth-card-eyebrow">Secure account access</span>
        <h2 class="auth-title">Welcome back</h2>
        <p class="auth-subtitle">Sign in to submit feedback or access the faculty dashboard.</p>

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        {{-- Errors --}}
        @if($errors->any())
            <div class="alert alert-error">
                ⚠️ {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form-control {{ $errors->has('email') ? 'is-invalid' : '' }}"
                    placeholder="you@example.com"
                    required
                    autofocus
                    autocomplete="email"
                >
                @error('email')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="password-input-wrap">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle" data-password-toggle="password" data-password-label="password" aria-label="Show password" aria-pressed="false">
                        <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
                            <circle cx="12" cy="12" r="2.5" />
                        </svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="m3 3 18 18M10.6 6.2A9.9 9.9 0 0 1 12 6c6 0 9.5 6 9.5 6a15.5 15.5 0 0 1-2.2 2.9M6.6 6.7C4 8.4 2.5 12 2.5 12s3.5 6 9.5 6c1.4 0 2.7-.3 3.8-.8M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <p class="session-note">
                <span aria-hidden="true">◷</span> You will stay signed in for up to one hour.
            </p>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                Sign In
            </button>
        </form>

        <div class="auth-footer-link">
            Don't have an account?
            <a href="{{ route('register') }}">Create one</a>
        </div>

    </div>
</div>
@endsection
