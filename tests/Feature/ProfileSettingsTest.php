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

    public function test_user_can_update_their_nickname_and_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'nickname' => 'OldNickname',
            'name' => 'OldNickname',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'nickname' => 'Juan_2026',
                'profile_photo' => UploadedFile::fake()->image('avatar.jpg', 120, 120),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Juan_2026', $user->nickname);
        $this->assertSame('Juan_2026', $user->name);
        $this->assertNotNull($user->profile_photo_path);
        Storage::disk('public')->assertExists($user->profile_photo_path);
    }

    public function test_user_can_remove_their_profile_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/existing.jpg', 'image');
        $user = User::factory()->create([
            'nickname' => 'Juan_01',
            'profile_photo_path' => 'profile-photos/existing.jpg',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'nickname' => 'Juan_01',
                'remove_profile_photo' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull($user->refresh()->profile_photo_path);
        Storage::disk('public')->assertMissing('profile-photos/existing.jpg');
    }

    public function test_profile_rejects_symbols_in_nickname_and_non_image_uploads(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'nickname' => 'Juan Cruz!',
                'profile_photo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ])
            ->assertSessionHasErrors(['nickname', 'profile_photo']);
    }

    public function test_profile_rejects_a_case_insensitive_duplicate_nickname(): void
    {
        User::factory()->create(['nickname' => 'TakenNickname']);
        $user = User::factory()->create(['nickname' => 'CurrentNickname']);

        $this->actingAs($user)
            ->patch(route('profile.update'), ['nickname' => 'takennickname'])
            ->assertSessionHasErrors(['nickname']);

        $this->assertSame('CurrentNickname', $user->refresh()->nickname);
    }
}
