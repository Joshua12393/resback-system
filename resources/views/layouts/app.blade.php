<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') — ResBack</title>
    <meta name="description" content="ResBack academic feedback portal and sentiment analytics.">

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
                <div class="brand-sub">{{ auth()->user()->isStudent() ? 'Student Feedback Portal' : 'Academic Feedback System' }}</div>
            </div>
            <button class="sidebar-close" id="sidebarClose" type="button" aria-label="Close navigation">×</button>
        </div>

        <nav class="sidebar-nav" aria-label="Main navigation">
            <div class="nav-section-label">Main</div>

            @if(auth()->user()->isAdmin() || auth()->user()->isFaculty())
            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="dashboard" /></span>
                Dashboard
            </a>
            @endif

            @unless(auth()->user()->isAdmin())
                @if(auth()->user()->isFaculty())
                    <div class="nav-section-label" style="margin-top:.75rem;">Quick Actions</div>
                @endif
                <a href="{{ route('feedback.create') }}" class="nav-link {{ request()->routeIs('feedback.create', 'feedback.thankyou') ? 'active' : '' }}">
                    <span class="nav-icon"><x-icon name="feedback" /></span>
                    {{ auth()->user()->isFaculty() ? 'View Feedback Form' : 'Share Feedback' }}
                </a>
                <a href="{{ route('feedback.history') }}" class="nav-link {{ request()->routeIs('feedback.history') ? 'active' : '' }}">
                    <span class="nav-icon"><x-icon name="history" /></span>
                    My Feedback
                </a>
            @endunless

            @if(auth()->user()->isAdmin())
                <a href="{{ route('accounts.index') }}"
                   class="nav-link {{ request()->routeIs('accounts.*') ? 'active' : '' }}">
                    <span class="nav-icon"><x-icon name="accounts" /></span>
                    Manage Accounts
                </a>
            @endif

            <div class="nav-section-label" style="margin-top:.75rem;">Account</div>
            <a href="{{ route('profile.edit') }}"
               class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <span class="nav-icon"><x-icon name="profile" /></span>
                Profile Settings
            </a>

        </nav>

        <div class="sidebar-footer">
            <div class="user-card">
                <a href="{{ route('profile.edit') }}" class="user-avatar" aria-label="Open profile settings">
                    @if(auth()->user()->profile_photo_url)
                        <img src="{{ auth()->user()->profile_photo_url }}" alt="">
                    @else
                        {{ strtoupper(substr(auth()->user()->display_name, 0, 1)) }}
                    @endif
                </a>
                <div class="user-info" style="flex:1; min-width:0;">
                    <div class="user-name">{{ auth()->user()->display_name }}</div>
                    <div class="user-role">{{ \Illuminate\Support\Str::headline(auth()->user()->role) }}</div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Logout" class="sidebar-logout" aria-label="Logout">
                        <x-icon name="logout" />
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
                <h1>@yield('page-title', auth()->user()->isStudent() ? 'Student Feedback Portal' : 'Feedback Portal')</h1>
                <div class="topbar-sub">@yield('page-subtitle', 'ResBack Feedback System')</div>
            </div>
            <div class="topbar-actions">
                @yield('topbar-actions')
                <x-theme-toggle />
            </div>
        </header>

        <main class="app-body {{ request()->routeIs('feedback.create', 'feedback.history', 'feedback.thankyou') ? 'portal-body' : '' }}">
            @if(session('success') || request()->routeIs('profile.edit'))
                @if(request()->routeIs('accounts.index', 'profile.edit'))
                    <dialog class="feedback-confirmation account-success-dialog" data-success-dialog @if(session('success')) data-auto-open @endif tabindex="-1" aria-labelledby="account-success-title" aria-describedby="account-success-message">
                        <div class="account-success-check" aria-hidden="true">✓</div>
                        <h2 id="account-success-title">All done!</h2>
                        <p id="account-success-message">{{ session('success') }}</p>
                        <form method="dialog">
                            <button type="submit" class="btn btn-primary">Got it</button>
                        </form>
                    </dialog>
                    @if(session('success'))
                        <noscript><div class="alert alert-success">{{ session('success') }}</div></noscript>
                    @endif
                @else
                    <div class="alert alert-success">✅ {{ session('success') }}</div>
                @endif
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
        const sidebar = document.getElementById('dashboardSidebar');
        const content = document.querySelector('.app-content');
        const mobile = window.matchMedia('(max-width: 900px)');

        if (!layout || !toggle) return;

        const setMenu = (open) => {
            open = open && mobile.matches;
            layout.classList.toggle('sidebar-open', open);
            toggle.setAttribute('aria-expanded', String(open));
            document.body.classList.toggle('menu-open', open);
            sidebar.inert = mobile.matches && !open;
            content.inert = open;
            if (open) {
                sidebar.setAttribute('role', 'dialog');
                sidebar.setAttribute('aria-modal', 'true');
                sidebar.setAttribute('aria-label', 'Navigation');
                close.focus();
            } else {
                sidebar.removeAttribute('role');
                sidebar.removeAttribute('aria-modal');
                sidebar.removeAttribute('aria-label');
            }
        };

        const closeMenu = () => { setMenu(false); if (mobile.matches) toggle.focus(); };

        toggle.addEventListener('click', () => setMenu(!layout.classList.contains('sidebar-open')));
        close?.addEventListener('click', closeMenu);
        overlay?.addEventListener('click', closeMenu);
        document.addEventListener('keydown', (event) => {
            if (!layout.classList.contains('sidebar-open')) return;
            if (event.key === 'Escape') closeMenu();
            if (event.key === 'Tab') {
                const controls = [...sidebar.querySelectorAll('a[href], button:not([disabled])')].filter(el => el.getClientRects().length);
                const first = controls[0], last = controls[controls.length - 1];
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        });
        mobile.addEventListener('change', () => setMenu(false));
        setMenu(false);
    })();
</script>
</body>
</html>
