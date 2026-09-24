<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:50'],
        ]);
        $search = trim($validated['search'] ?? '');

        $users = User::query()
            ->select(['id', 'nickname', 'role', 'is_active', 'created_at'])
            ->when($search !== '', fn ($query) => $query->where('nickname', 'like', "%{$search}%"))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('dashboard.accounts.index', compact('users', 'search'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            return back()->with('error', 'You cannot change your own role while signed in.');
        }

        if (! $actor->isSuperAdmin() && $user->isAdmin()) {
            return back()->with('error', 'Only a super administrator can change an administrator role.');
        }

        $allowedRoles = $actor->isSuperAdmin()
            ? ['student', 'faculty', 'admin', 'super_admin']
            : ['student', 'faculty'];

        if (! in_array($request->string('role')->toString(), $allowedRoles, true)) {
            return back()->with('error', 'Only a super administrator can assign administrator roles.');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in($allowedRoles)],
        ]);

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "{$user->display_name}'s role was updated to {$validated['role']}.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            return back()->with('error', 'You cannot deactivate your own account while signed in.');
        }

        if (! $actor->isSuperAdmin() && $user->isAdmin()) {
            return back()->with('error', 'Only a super administrator can change an administrator account status.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $status = $user->is_active ? 'reactivated' : 'deactivated';

        return back()->with('success', "{$user->display_name}'s account was {$status}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            return back()->with('error', 'You cannot delete your own account while signed in.');
        }

        if (! $actor->isSuperAdmin()) {
            return back()->with('error', 'Only a super administrator can delete accounts.');
        }

        $nickname = $user->display_name;
        $user->delete();

        return back()->with('success', "{$nickname}'s account was deleted.");
    }
}
