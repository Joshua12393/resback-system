<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_form_is_fixed_to_ccis_only(): void
    {
        $this->seed(CategorySeeder::class);
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->get(route('feedback.create'));

        $response->assertOk()
            ->assertSee('Department')
            ->assertSee('value="CCIS"', false)
            ->assertDontSee('COE')
            ->assertDontSee('Coming soon');

        $this->assertSame(['CCIS'], Category::active()->pluck('name')->all());
    }

    public function test_only_ccis_can_be_submitted(): void
    {
        $this->seed(CategorySeeder::class);
        $student = User::factory()->create(['role' => 'student']);
        $cas = Category::create(['name' => 'CAS', 'slug' => 'cas', 'is_active' => true]);

        $this->actingAs($student)
            ->post(route('feedback.store'), [
                'category_id' => $cas->id,
                'content' => 'This CAS submission should be blocked for now.',
            ])
            ->assertSessionHasErrors([
                'category_id' => 'Feedback submissions are currently available for CCIS only.',
            ]);

        $this->assertDatabaseCount('feedbacks', 0);
    }

    public function test_feedback_submission_requires_an_active_category(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $inactiveCategory = Category::create([
            'name' => 'Inactive Department',
            'slug' => 'inactive-department',
            'is_active' => false,
        ]);

        $this->actingAs($student)
            ->post(route('feedback.store'), ['content' => 'This feedback has no selected department.'])
            ->assertSessionHasErrors('category_id');

        $this->actingAs($student)
            ->post(route('feedback.store'), [
                'category_id' => $inactiveCategory->id,
                'content' => 'This feedback uses an inactive department.',
            ])
            ->assertSessionHasErrors('category_id');
    }

    public function test_category_seeder_deactivates_existing_non_ccis_categories(): void
    {
        $cas = Category::create(['name' => 'CAS', 'slug' => 'cas', 'is_active' => true]);

        $this->seed(CategorySeeder::class);

        $this->assertFalse($cas->refresh()->is_active);
        $this->assertSame(['CCIS'], Category::active()->pluck('name')->all());
    }
}
