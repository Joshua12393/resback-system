<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'nickname' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[\pL\pM\pN_]+$/u',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    $nicknameExists = User::query()
                        ->whereKeyNot($user->getKey())
                        ->whereRaw('LOWER(nickname) = ?', [mb_strtolower(trim((string) $value), 'UTF-8')])
                        ->exists();

                    if ($nicknameExists) {
                        $fail('That nickname is already in use.');
                    }
                },
            ],
            'profile_photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024)],
            'remove_profile_photo' => ['nullable', 'boolean'],
        ], [
            'nickname.regex' => 'The nickname may contain letters, numbers, and underscores only.',
            'profile_photo.max' => 'The profile photo must not be larger than 2 MB.',
        ]);

        $nickname = trim($validated['nickname']);

        $attributes = [
            'nickname' => $nickname,
            'name' => $nickname,
        ];

        if ($request->hasFile('profile_photo')) {
            $newPath = $request->file('profile_photo')->store('profile-photos', 'public');

            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $attributes['profile_photo_path'] = $newPath;
        } elseif ($request->boolean('remove_profile_photo') && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $attributes['profile_photo_path'] = null;
        }

        $user->update($attributes);

        return back()->with('success', 'Your profile was updated successfully.');
    }
}
