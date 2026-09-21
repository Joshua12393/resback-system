<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — ResBack Admin</title>
    <meta name="description" content="ResBack administration dashboard for managing student feedback and sentiment analytics.">

    @include('partials.theme-init')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="dashboard-page">
<div class="app-layout">
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
    <aside class="sidebar" id="dashboardSidebar">
        <div class="sidebar-brand">
            <div class="brand-seal brand-seal-small">CCIS</div>
            <div>
                <div class="brand-text">ResBack</div>
                <div class="brand-sub">Academic Feedback System</div>
            </div>
            <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Close navigation">×</button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>

            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-icon" aria-hidden="true">▦</span>
                Dashboard
            </a>

            @if(auth()->user()->isAdmin())
                <a href="{{ route('accounts.index') }}"
                   class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                    <span class="nav-icon" aria-hidden="true">◎</span>
                    Manage Accounts
                </a>
            @endif

            <div class="nav-section-label" style="margin-top:.75rem;">Account</div>
            <a href="{{ route('profile.edit') }}"
               class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <span class="nav-icon" aria-hidden="true">◉</span>
                Profile Settings
            </a>

            @if(auth()->user()->isFaculty())
                <div class="nav-section-label" style="margin-top:.75rem;">Quick Actions</div>

                <a href="{{ route('feedback.create') }}" target="_blank" class="nav-link">
                    <span class="nav-icon" aria-hidden="true">□</span>
                    View Feedback Form
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="user-card">
                <a href="{{ route('profile.edit') }}" class="user-avatar" aria-label="Open profile settings">
                    @if(auth()->user()->profile_photo_url)
                        <img src="{{ auth()->user()->profile_photo_url }}" alt="">
                    @else
                        {{ strtoupper(substr(auth()->user()->display_first_name, 0, 1)) }}
                    @endif
                </a>
                <div class="user-info" style="flex:1; min-width:0;">
                    <div class="user-name">{{ auth()->user()->display_first_name }}</div>
                    <div class="user-role">{{ \Illuminate\Support\Str::headline(auth()->user()->role) }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Logout" class="sidebar-logout" aria-label="Logout">
                        ↗
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ═══════ MAIN CONTENT ═══════ --}}
    <div class="app-content">
        <header class="app-topbar">
            <button class="menu-toggle" id="menuToggle" type="button" aria-controls="dashboardSidebar" aria-expanded="false" aria-label="Open navigation">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-heading">
                <h1>@yield('page-title', 'Dashboard')</h1>
                <div class="topbar-sub">@yield('page-subtitle', 'ResBack Feedback System')</div>
            </div>
            <div class="topbar-actions">
                @yield('topbar-actions')
                <x-theme-toggle />
            </div>
        </header>

        <main class="app-body">
            @if(session('success'))
                <div class="alert alert-success">✅ {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">❌ {{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>

</div>

@stack('scripts')
<script>
    (() => {
        const layout = document.querySelector('.app-layout');
        const toggle = document.getElementById('menuToggle');
        const close = document.getElementById('sidebarClose');
        const overlay = document.getElementById('sidebarOverlay');

        if (!layout || !toggle) return;

        const setMenu = (open) => {
            layout.classList.toggle('sidebar-open', open);
            toggle.setAttribute('aria-expanded', String(open));
            document.body.classList.toggle('menu-open', open);
        };

        toggle.addEventListener('click', () => setMenu(!layout.classList.contains('sidebar-open')));
        close?.addEventListener('click', () => setMenu(false));
        overlay?.addEventListener('click', () => setMenu(false));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') setMenu(false);
        });
    })();
</script>
</body>
</html>
