<?php

namespace App\Http\Controllers;

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
        $nameRule = ['required', 'string', 'max:100', 'regex:/^[\pL\pM]+(?: [\pL\pM]+)*$/u'];

        $validated = $request->validate([
            'first_name' => $nameRule,
            'middle_name' => ['nullable', 'string', 'max:100', 'regex:/^[\pL\pM]+(?: [\pL\pM]+)*$/u'],
            'last_name' => $nameRule,
            'profile_photo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2 * 1024)],
            'remove_profile_photo' => ['nullable', 'boolean'],
        ], [
            'first_name.regex' => 'The first name may contain letters only. A single space is allowed between compound names.',
            'middle_name.regex' => 'The middle name may contain letters only. A single space is allowed between compound names.',
            'last_name.regex' => 'The last name may contain letters only. A single space is allowed between compound names.',
            'profile_photo.max' => 'The profile photo must not be larger than 2 MB.',
        ]);

        $user = $request->user();
        $firstName = trim(preg_replace('/\s+/u', ' ', $validated['first_name']));
        $middleName = isset($validated['middle_name'])
            ? trim(preg_replace('/\s+/u', ' ', $validated['middle_name']))
            : null;
        $lastName = trim(preg_replace('/\s+/u', ' ', $validated['last_name']));

        $attributes = [
            'first_name' => $firstName,
            'middle_name' => $middleName ?: null,
            'last_name' => $lastName,
            'name' => implode(' ', array_filter([$firstName, $middleName, $lastName])),
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
