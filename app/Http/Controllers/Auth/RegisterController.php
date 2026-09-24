<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    /**
     * Show the student registration form.
     */
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nickname' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[\pL\pM\pN_]+$/u',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (User::query()->whereRaw('LOWER(nickname) = ?', [mb_strtolower(trim((string) $value), 'UTF-8')])->exists()) {
                        $fail('That nickname is already in use.');
                    }
                },
            ],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'nickname.regex' => 'The nickname may contain letters, numbers, and underscores only.',
        ]);

        $nickname = trim($validated['nickname']);

        $user = User::create([
            'name'     => $nickname,
            'nickname' => $nickname,
            'email'    => $validated['email'],
            'role'     => 'student',
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('feedback.create');
    }
}
