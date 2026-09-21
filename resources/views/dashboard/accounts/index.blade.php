@extends('layouts.app')

@section('title', 'Manage Accounts')
@section('page-title', 'Manage Accounts')
@section('page-subtitle', 'Assign roles and control access to ResBack')

@section('content')
    <section class="card-dark">
        <div class="card-header">
            <div>
                <h2>User accounts</h2>
                <p style="font-size:.8rem;color:var(--gray-500);margin-top:.25rem;">
                    New registrations start as students. Only super administrators can assign or manage the protected Super Admin role.
                </p>
            </div>
            <span style="font-size:.8rem;color:var(--gray-500);">{{ number_format($users->total()) }} accounts</span>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>User</th><th>Role</th><th>Status</th><th>Registered</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>
                            <strong style="color:var(--gray-900);">{{ $user->name }}</strong>
                            @if(auth()->user()->is($user))
                                <span class="badge badge-neutral" style="margin-left:.35rem;">You</span>
                            @endif
                            <div style="font-size:.78rem;color:var(--gray-500);margin-top:.2rem;">{{ $user->email }}</div>
                        </td>
                        <td>
                            @if(auth()->user()->is($user) || ($user->isSuperAdmin() && ! auth()->user()->isSuperAdmin()))
                                <span class="badge badge-pending">{{ \Illuminate\Support\Str::headline($user->role) }}</span>
                            @else
                                <form action="{{ route('accounts.role', $user) }}" method="POST" style="display:flex;gap:.5rem;align-items:center;">
                                    @csrf
                                    @method('PATCH')
                                    <select name="role" class="form-control" style="min-width:110px;padding:.45rem .65rem;" aria-label="Role for {{ $user->name }}">
                                        @foreach(auth()->user()->isSuperAdmin() ? ['student', 'faculty', 'admin', 'super_admin'] : ['student', 'faculty', 'admin'] as $role)
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
                            @if(! auth()->user()->is($user) && (! $user->isSuperAdmin() || auth()->user()->isSuperAdmin()))
                                <div style="display:flex;gap:.5rem;align-items:center;">
                                    <form action="{{ route('accounts.status', $user) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-ghost btn-sm" type="submit">
                                            {{ $user->is_active ? 'Deactivate' : 'Reactivate' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('accounts.destroy', $user) }}" method="POST"
                                          onsubmit="return confirm('Permanently delete {{ addslashes($user->name) }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                                    </form>
                                </div>
                            @else
                                <span style="font-size:.78rem;color:var(--gray-500);">
                                    {{ auth()->user()->is($user) ? 'Protected current account' : 'Protected Super Admin account' }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div style="padding:1rem;">{{ $users->links() }}</div>
        @endif
    </section>
@endsection
