<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Account') — ResBack</title>
    <meta name="description" content="Sign in or create a ResBack student account.">

    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page">
    <div class="auth-theme-toggle"><x-theme-toggle /></div>
    <div class="auth-shell">
        <section class="auth-intro" aria-label="About ResBack">
            <a href="{{ route('feedback.create') }}" class="institution-brand institution-brand-light">
                <span class="brand-seal" aria-hidden="true">CCIS</span>
                <span>
                    <strong>ResBack</strong>
                    <small>CCIS Feedback System</small>
                </span>
            </a>

            <div class="auth-intro-copy">
                <span class="academic-eyebrow">College of Computing and Information Sciences</span>
                <h1>A clearer way to hear every student voice.</h1>
                <p>Share experiences, identify campus concerns, and support evidence-based improvements in our college community.</p>
            </div>

            <div class="auth-assurance">
                <span class="assurance-mark" aria-hidden="true">✓</span>
                <span><strong>Private by design</strong><small>Your identity stays hidden from feedback reviewers.</small></span>
            </div>
        </section>

        <main class="auth-main">
            @yield('content')
            <p class="auth-copyright">© {{ date('Y') }} ResBack · CCIS Academic Project</p>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
