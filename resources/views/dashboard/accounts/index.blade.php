@extends('layouts.app')

@section('title', 'Manage Accounts')
@section('page-title', 'Manage Accounts')
@section('page-subtitle', 'Find accounts by nickname, assign roles, and control access')

@section('content')
    <section class="card-dark">
        <div class="card-header">
            <div>
                <h2>User accounts</h2>
                <p style="font-size:.8rem;color:var(--gray-500);margin-top:.25rem;">
                    Only nicknames are shown. Admins manage Student and Faculty accounts; Super Admin controls administrator roles and deletion.
                </p>
            </div>
            <span style="font-size:.8rem;color:var(--gray-500);">{{ number_format($users->total()) }} accounts</span>
        </div>

        <div class="account-search-bar">
            <form action="{{ route('accounts.index') }}" method="GET" class="account-search-form" role="search">
                <div class="account-search-field">
                    <label for="account-search" class="form-label">Search nickname</label>
                    <input
                        id="account-search"
                        name="search"
                        type="search"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Enter a nickname"
                        maxlength="50"
                    >
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                @if($search !== '')
                    <a href="{{ route('accounts.index') }}" class="btn btn-ghost">Clear</a>
                @endif
            </form>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Nickname</th><th>Role</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    @php
                        $canManageAccount = ! auth()->user()->is($user)
                            && (auth()->user()->isSuperAdmin() || ! $user->isAdmin());
                        $canDeleteAccount = auth()->user()->isSuperAdmin()
                            && ! auth()->user()->is($user);
                    @endphp
                    <tr>
                        <td>
                            <strong style="color:var(--gray-900);">{{ $user->nickname }}</strong>
                            @if(auth()->user()->is($user))
                                <span class="badge badge-neutral" style="margin-left:.35rem;">You</span>
                            @endif
                        </td>
                        <td>
                            @if(! $canManageAccount)
                                <span class="badge badge-pending">{{ \Illuminate\Support\Str::headline($user->role) }}</span>
                            @else
                                <form action="{{ route('accounts.role', $user) }}" method="POST" style="display:flex;gap:.5rem;align-items:center;">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="form-control" style="min-width:110px;padding:.45rem .65rem;" aria-label="Role for {{ $user->nickname }}">
                                        @foreach(auth()->user()->isSuperAdmin() ? ['student', 'faculty', 'admin', 'super_admin'] : ['student', 'faculty'] as $role)
                                            <option value="{{ $role }}" @selected($user->role === $role)>{{ \Illuminate\Support\Str::headline($role) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                </form>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $user->is_active ? 'positive' : 'negative' }}">
                                {{ $user->is_active ? 'Active' : 'Deactivated' }}
                            </span>
                        </td>
                        <td style="white-space:nowrap;">{{ $user->created_at->format('M d, Y') }}</td>
                        <td>
                            @if($canManageAccount)
                                <div style="display:flex;gap:.5rem;align-items:center;">
                                    <form action="{{ route('accounts.status', $user) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-ghost btn-sm" type="submit">
                                            {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    </form>
                                    @if($canDeleteAccount)
                                        <form action="{{ route('accounts.destroy', $user) }}" method="POST"
                                              onsubmit="return confirm(@js('Permanently delete '.$user->nickname.'? This cannot be undone.'));">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                        </form>
                                    @endif
                                </div>
                            @else
                                <span style="font-size:.78rem;color:var(--gray-500);">
                                    {{ auth()->user()->is($user) ? 'Protected current account' : 'Protected administrator account' }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <h3>No matching accounts</h3>
                                <p>Try a different nickname.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div style="padding:1rem;">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
