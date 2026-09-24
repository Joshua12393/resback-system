<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_registration_pages_are_available_to_guests(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign In')
            ->assertSee('data-theme-toggle', false)
            ->assertSee('data-password-toggle="password"', false);
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Create an account')
            ->assertSee('Nickname')
            ->assertDontSee('First Name')
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false);
    }

    public function test_public_registration_creates_only_a_student_account(): void
    {
        $response = $this->post(route('register'), [
            'nickname' => 'Student_01',
            'email' => 'student@example.test',
            'role' => 'admin',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('feedback.create'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Student_01',
            'nickname' => 'Student_01',
            'email' => 'student@example.test',
            'role' => 'student',
        ]);
    }

    public function test_registration_accepts_letters_numbers_and_underscores_in_nickname(): void
    {
        $this->post(route('register'), [
            'nickname' => 'Juan_2026',
            'email' => 'nickname@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('feedback.create'));

        $this->assertDatabaseHas('users', [
            'nickname' => 'Juan_2026',
            'email' => 'nickname@example.test',
        ]);
    }

    public function test_registration_rejects_spaces_and_symbols_in_nickname(): void
    {
        $this->post(route('register'), [
            'nickname' => 'Juan Cruz!',
            'email' => 'symbol-nickname@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors(['nickname']);

        $this->assertDatabaseMissing('users', ['email' => 'symbol-nickname@example.test']);
    }

    public function test_registration_rejects_a_case_insensitive_duplicate_nickname(): void
    {
        User::factory()->create(['nickname' => 'ResBackUser']);

        $this->post(route('register'), [
            'nickname' => 'resbackuser',
            'email' => 'duplicate-nickname@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertSessionHasErrors(['nickname']);

        $this->assertDatabaseMissing('users', ['email' => 'duplicate-nickname@example.test']);
    }

    public function test_student_login_redirects_to_feedback_and_logout_ends_the_session(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('login'), [
            'email' => $student->email,
            'password' => 'Password123!',
            'remember' => true,
        ])->assertRedirect(route('feedback.create'));

        $this->assertAuthenticatedAs($student);
        $this->get(route('feedback.create'))->assertOk();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_root_route_sends_students_to_feedback_and_staff_to_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $admin = User::factory()->create(['role' => 'admin']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($student)->get(route('home'))->assertRedirect(route('feedback.create'));
        $this->actingAs($faculty)->get(route('home'))->assertRedirect(route('dashboard'));
        $this->actingAs($admin)->get(route('home'))->assertRedirect(route('dashboard'));
        $this->actingAs($superAdmin)->get(route('home'))->assertRedirect(route('dashboard'));
    }

    public function test_staff_login_ignores_a_stale_student_page_intended_url(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('Password123!'),
        ]);

        $this->withSession(['url.intended' => route('feedback.create')])
            ->post(route('login'), [
                'email' => $admin->email,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('url.intended'));
    }

    public function test_feedback_routes_require_authentication_and_sessions_last_one_hour(): void
    {
        $this->get(route('home'))->assertRedirect(route('login'));
        $this->get(route('feedback.create'))->assertRedirect(route('login'));
        $this->post(route('feedback.store'))->assertRedirect(route('login'));
        $this->assertSame(60, config('session.lifetime'));
        $this->assertFalse(config('session.expire_on_close'));
    }

    public function test_deactivated_user_cannot_log_in_and_an_existing_session_is_ended(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'is_active' => false,
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)
            ->get(route('feedback.create'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        $this->assertGuest();
    }

    #[DataProvider('authorizedRoles')]
    public function test_authorized_users_can_log_in_and_access_dashboard_and_export(string $role): void
    {
        $user = User::factory()->create([
            'role' => $role,
            'password' => Hash::make('Password123!'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('feedback.export'))->assertOk()->assertDownload();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authorizedRoles(): array
    {
        return [
            'super admin' => ['super_admin'],
            'admin' => ['admin'],
            'faculty' => ['faculty'],
        ];
    }
}
