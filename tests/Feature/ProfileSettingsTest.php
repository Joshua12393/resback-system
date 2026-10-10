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
        $this->delete(route('profile.photo.destroy'))->assertRedirect(route('login'));
        $this->post(route('profile.photo.store'))->assertRedirect(route('login'));
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

    public function test_photo_upload_saves_immediately_without_updating_nickname(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/previous.jpg', 'image');
        $user = User::factory()->create([
            'nickname' => 'OriginalNickname',
            'name' => 'OriginalNickname',
            'profile_photo_path' => 'profile-photos/previous.jpg',
        ]);
        $response = $this->actingAs($user)->postJson(route('profile.photo.store'), [
            'profile_photo' => UploadedFile::fake()->image('new-avatar.png', 120, 120),
            'nickname' => 'UnsavedNickname',
        ])->assertOk()->assertJsonPath('message', 'Your profile photo was updated successfully.');

        $user->refresh();
        $this->assertSame('OriginalNickname', $user->nickname);
        $this->assertSame('OriginalNickname', $user->name);
        $this->assertNotSame('profile-photos/previous.jpg', $user->profile_photo_path);
        $response->assertJsonPath('photo_url', $user->profile_photo_url);
        Storage::disk('public')->assertExists($user->profile_photo_path);
        Storage::disk('public')->assertMissing('profile-photos/previous.jpg');
    }

    public function test_invalid_photo_upload_preserves_the_existing_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/previous.jpg', 'image');
        $user = User::factory()->create(['profile_photo_path' => 'profile-photos/previous.jpg']);
        $this->actingAs($user)->postJson(route('profile.photo.store'), [
            'profile_photo' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
        ])->assertUnprocessable()->assertJsonValidationErrors('profile_photo');
        $this->assertSame('profile-photos/previous.jpg', $user->refresh()->profile_photo_path);
        Storage::disk('public')->assertExists('profile-photos/previous.jpg');
    }

    public function test_photo_removal_is_independent_of_other_profile_changes(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile-photos/own.jpg', 'image');
        Storage::disk('public')->put('profile-photos/other.jpg', 'image');
        $user = User::factory()->create([
            'nickname' => 'OriginalNickname',
            'name' => 'OriginalNickname',
            'profile_photo_path' => 'profile-photos/own.jpg',
        ]);
        $other = User::factory()->create(['profile_photo_path' => 'profile-photos/other.jpg']);

        $this->actingAs($user)->delete(route('profile.photo.destroy'), [
            'nickname' => 'UnsavedNickname',
            'user_id' => $other->id,
        ])->assertRedirect(route('profile.edit'))->assertSessionHas('success', 'Your profile photo was removed.');

        $user->refresh();
        $this->assertNull($user->profile_photo_path);
        $this->assertSame('OriginalNickname', $user->nickname);
        $this->assertSame('OriginalNickname', $user->name);
        Storage::disk('public')->assertMissing('profile-photos/own.jpg');
        Storage::disk('public')->assertExists('profile-photos/other.jpg');
        $this->assertSame('profile-photos/other.jpg', $other->refresh()->profile_photo_path);
    }

    public function test_photo_removal_succeeds_when_no_photo_is_present(): void
    {
        $user = User::factory()->create(['profile_photo_path' => null]);
        $this->actingAs($user)->delete(route('profile.photo.destroy'))
            ->assertRedirect(route('profile.edit'))->assertSessionHas('success');
        $this->assertNull($user->refresh()->profile_photo_path);
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
