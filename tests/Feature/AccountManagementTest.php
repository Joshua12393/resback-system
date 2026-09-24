<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_open_account_management(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $faculty = User::factory()->create(['role' => 'faculty']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($superAdmin)->get(route('accounts.index'))->assertOk()->assertSee('Manage Accounts');
        $this->actingAs($admin)->get(route('accounts.index'))->assertOk()->assertSee('Manage Accounts');
        $this->actingAs($faculty)->get(route('accounts.index'))->assertForbidden();
        $this->actingAs($student)->get(route('accounts.index'))->assertForbidden();
    }

    public function test_account_management_uses_compact_project_pagination(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->count(15)->create(['role' => 'student']);

        $this->actingAs($admin)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('resback-pagination', false)
            ->assertSee('pagination-arrow', false)
            ->assertDontSee('w-5 h-5', false);
    }

    public function test_account_management_shows_and_searches_nicknames_without_exposing_email_or_real_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'nickname' => 'AccountManager']);
        $target = User::factory()->create([
            'nickname' => 'BlueFalcon',
            'name' => 'Private Legal Name',
            'email' => 'private-person@example.test',
        ]);
        $other = User::factory()->create([
            'nickname' => 'GreenTurtle',
            'name' => 'Another Private Name',
            'email' => 'another-private@example.test',
        ]);

        $response = $this->actingAs($admin)->get(route('accounts.index', ['search' => 'blue']));

        $response->assertOk()
            ->assertSee($target->nickname)
            ->assertDontSee($other->nickname)
            ->assertDontSee($target->email)
            ->assertDontSee($target->name)
            ->assertDontSee($other->email)
            ->assertDontSee($other->name);
    }

    public function test_admin_can_assign_student_and_faculty_roles_and_change_their_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)
            ->patch(route('accounts.role', $user), ['role' => 'faculty'])
            ->assertRedirect();
        $this->assertSame('faculty', $user->refresh()->role);

        $this->actingAs($admin)->patch(route('accounts.status', $user))->assertRedirect();
        $this->assertFalse($user->refresh()->is_active);

        $this->actingAs($admin)->patch(route('accounts.status', $user))->assertRedirect();
        $this->assertTrue($user->refresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('accounts.role', $user), ['role' => 'student'])
            ->assertRedirect();
        $this->assertSame('student', $user->refresh()->role);

        $this->actingAs($admin)
            ->delete(route('accounts.destroy', $user))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_assign_admin_roles_or_manage_another_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $candidate = User::factory()->create(['role' => 'student']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('accounts.role', $candidate), ['role' => 'admin'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('student', $candidate->refresh()->role);

        $this->actingAs($admin)
            ->patch(route('accounts.role', $otherAdmin), ['role' => 'student'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->actingAs($admin)
            ->patch(route('accounts.status', $otherAdmin))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->actingAs($admin)
            ->delete(route('accounts.destroy', $otherAdmin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $otherAdmin->refresh();
        $this->assertSame('admin', $otherAdmin->role);
        $this->assertTrue($otherAdmin->is_active);
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_admin_interface_hides_admin_management_and_all_delete_controls(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee(route('accounts.role', $student), false)
            ->assertSee(route('accounts.status', $student), false)
            ->assertDontSee('action="'.route('accounts.destroy', $student).'" method="POST"', false)
            ->assertDontSee(route('accounts.role', $otherAdmin), false)
            ->assertDontSee(route('accounts.status', $otherAdmin), false)
            ->assertDontSee('action="'.route('accounts.destroy', $otherAdmin).'" method="POST"', false);
    }

    public function test_admin_cannot_change_deactivate_or_delete_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch(route('accounts.role', $admin), ['role' => 'student'])->assertRedirect();
        $this->actingAs($admin)->patch(route('accounts.status', $admin))->assertRedirect();
        $this->actingAs($admin)->delete(route('accounts.destroy', $admin))->assertRedirect();

        $admin->refresh();
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_faculty_cannot_call_account_management_actions_directly(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($faculty)
            ->patch(route('accounts.role', $student), ['role' => 'admin'])
            ->assertForbidden();
        $this->actingAs($faculty)->patch(route('accounts.status', $student))->assertForbidden();
        $this->actingAs($faculty)->delete(route('accounts.destroy', $student))->assertForbidden();

        $this->assertSame('student', $student->refresh()->role);
        $this->assertTrue($student->is_active);
    }

    public function test_admin_cannot_manage_or_demote_a_super_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($admin)
            ->patch(route('accounts.role', $superAdmin), ['role' => 'student'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->actingAs($admin)
            ->patch(route('accounts.status', $superAdmin))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->actingAs($admin)
            ->delete(route('accounts.destroy', $superAdmin))
            ->assertRedirect()
            ->assertSessionHas('error');

        $superAdmin->refresh();
        $this->assertSame('super_admin', $superAdmin->role);
        $this->assertTrue($superAdmin->is_active);
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_only_super_admin_can_assign_the_super_admin_role(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $adminCandidate = User::factory()->create(['role' => 'student']);
        $superAdminCandidate = User::factory()->create(['role' => 'student']);

        $this->actingAs($admin)
            ->patch(route('accounts.role', $adminCandidate), ['role' => 'super_admin'])
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame('student', $adminCandidate->refresh()->role);

        $this->actingAs($superAdmin)
            ->patch(route('accounts.role', $superAdminCandidate), ['role' => 'super_admin'])
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame('super_admin', $superAdminCandidate->refresh()->role);
    }

    public function test_super_admin_can_manage_and_delete_an_admin_account(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($superAdmin)
            ->patch(route('accounts.status', $admin))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertFalse($admin->refresh()->is_active);

        $this->actingAs($superAdmin)
            ->delete(route('accounts.destroy', $admin))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }
}
