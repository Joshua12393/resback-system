<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('dashboard.accounts.index', compact('users'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            return back()->with('error', 'You cannot change your own role while signed in.');
        }

        if ($user->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'Only a super administrator can change a super administrator role.');
        }

        if ($request->string('role')->toString() === 'super_admin' && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'Only a super administrator can assign the super administrator role.');
        }

        $allowedRoles = $actor->isSuperAdmin()
            ? ['student', 'faculty', 'admin', 'super_admin']
            : ['student', 'faculty', 'admin'];

        $validated = $request->validate([
            'role' => ['required', Rule::in($allowedRoles)],
        ]);

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "{$user->name}'s role was updated to {$validated['role']}.");
    }

    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            return back()->with('error', 'You cannot deactivate your own account while signed in.');
        }

        if ($user->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'Only a super administrator can change a super administrator account status.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $status = $user->is_active ? 'reactivated' : 'deactivated';

        return back()->with('success', "{$user->name}'s account was {$status}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            return back()->with('error', 'You cannot delete your own account while signed in.');
        }

        if ($user->isSuperAdmin() && ! $actor->isSuperAdmin()) {
            return back()->with('error', 'Only a super administrator can delete a super administrator account.');
        }

        $name = $user->name;
        $user->delete();

        return back()->with('success', "{$name}'s account was deleted.");
    }
}
