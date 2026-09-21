<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FeedbackDeletionTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('administratorRoles')]
    public function test_administrators_can_permanently_delete_feedback_and_its_analysis(string $role): void
    {
        $administrator = User::factory()->create(['role' => $role]);
        $feedback = $this->createAnalyzedFeedback();
        $resultId = $feedback->sentimentResult()->value('id');

        $this->actingAs($administrator)
            ->from(route('dashboard'))
            ->delete(route('feedback.destroy', $feedback))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'The feedback and its analysis were permanently deleted.');

        $this->assertDatabaseMissing('feedbacks', ['id' => $feedback->id]);
        $this->assertDatabaseMissing('sentiment_results', ['id' => $resultId]);
    }

    public function test_faculty_cannot_delete_feedback_or_see_the_delete_action(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $feedback = $this->createAnalyzedFeedback();

        $this->actingAs($faculty)
            ->delete(route('feedback.destroy', $feedback))
            ->assertForbidden();

        $this->assertDatabaseHas('feedbacks', ['id' => $feedback->id]);

        $this->actingAs($faculty)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('feedback.destroy', $feedback));
    }

    public function test_admin_sees_the_feedback_delete_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $feedback = $this->createAnalyzedFeedback();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('feedback.destroy', $feedback));
    }

    /** @return array<string, array{string}> */
    public static function administratorRoles(): array
    {
        return [
            'admin' => ['admin'],
            'super admin' => ['super_admin'],
        ];
    }

    private function createAnalyzedFeedback(): Feedback
    {
        $category = Category::firstOrCreate(
            ['slug' => 'ccis'],
            ['name' => 'CCIS', 'is_active' => true],
        );
        $feedback = Feedback::create([
            'category_id' => $category->id,
            'content' => 'The laboratory internet connection is unreliable.',
            'status' => 'analyzed',
        ]);
        $feedback->sentimentResult()->create([
            'sentiment' => 'negative',
            'confidence' => .9,
            'keywords' => ['internet'],
            'concern_topics' => ['Wi-Fi / Internet'],
            'detected_languages' => ['English'],
            'language_category' => 'English',
            'language_confidence' => 1,
        ]);

        return $feedback;
    }
}
