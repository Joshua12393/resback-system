<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Submit Feedback') — ResBack</title>
    <meta name="description" content="Submit confidential campus feedback securely. Your voice matters.">

    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="guest-page">
<div class="guest-layout">
    <header class="guest-header">
        <a href="{{ route('feedback.create') }}" class="institution-brand">
            <span class="brand-seal" aria-hidden="true">CCIS</span>
            <span>
                <strong>ResBack</strong>
                <small>Student Feedback Portal</small>
            </span>
        </a>
        <nav class="guest-nav" aria-label="Account navigation">
            @auth
                @if(auth()->user()->isAdmin() || auth()->user()->isFaculty())
                    <a href="{{ route('dashboard') }}" class="header-link">Dashboard</a>
                @endif
                @unless(auth()->user()->isAdmin())
                    <a href="{{ route('feedback.history') }}" class="header-link {{ request()->routeIs('feedback.history') ? 'header-link-active' : '' }}">My Feedback</a>
                @endunless
                <span class="guest-user">{{ auth()->user()->display_first_name }}</span>
                <a href="{{ route('profile.edit') }}" class="header-profile-avatar" aria-label="Open profile settings" title="Profile settings">
                    @if(auth()->user()->profile_photo_url)
                        <img src="{{ auth()->user()->profile_photo_url }}" alt="">
                    @else
                        {{ strtoupper(substr(auth()->user()->display_first_name, 0, 1)) }}
                    @endif
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="header-link header-button">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="header-link">Sign In</a>
            @endauth
            <x-theme-toggle />
        </nav>
    </header>

    {{-- ═══════ MAIN ═══════ --}}
    <main class="guest-main">
        @yield('content')
    </main>

    <footer class="guest-footer">
        <span>© {{ date('Y') }} ResBack — CCIS Feedback System</span>
        <span>Powered by Gemma Sentiment Analysis</span>
    </footer>

</div>

@stack('scripts')
</body>
</html>
