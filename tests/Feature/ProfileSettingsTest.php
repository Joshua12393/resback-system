<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_settings_require_authentication(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->patch(route('profile.update'))->assertRedirect(route('login'));
    }

    public function test_user_can_update_their_name_and_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'first_name' => 'Old',
            'middle_name' => null,
            'last_name' => 'Name',
            'name' => 'Old Name',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => 'Juan',
                'middle_name' => 'Luan',
                'last_name' => 'Dela Cruz',
                'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 120, 120),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Juan Luan Dela Cruz', $user->name);
        $this->assertSame('Juan', $user->first_name);
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_user_can_remove_their_profile_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/existing.jpg', 'image');
        $user = User::factory()->create([
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'profile_photo_path' => 'profile-photos/existing.jpg',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => 'Juan',
                'middle_name' => '',
                'last_name' => 'Cruz',
                'remove_profile_photo' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($user->refresh()->profile_photo_path);
        Storage::disk('public')->assertMissing('profile-photos/existing.jpg');
    }

    public function test_profile_rejects_symbols_in_names_and_non_image_uploads(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'first_name' => 'Juan1',
                'middle_name' => '',
                'last_name' => 'Cruz!',
                'profile_photo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors(['first_name', 'last_name', 'profile_photo']);
    }
}
