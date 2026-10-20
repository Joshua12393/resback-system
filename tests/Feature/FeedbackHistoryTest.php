<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\SentimentResult;
use App\Models\User;
use App\Services\SentimentService;
use App\Support\FeedbackSubmitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class FeedbackHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_submission_is_linked_to_the_authenticated_user(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $category = Category::create([
            'name' => 'CCIS',
            'slug' => 'ccis',
            'is_active' => true,
        ]);

        $this->mock(SentimentService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('analyze')->once();
        });

        $this->actingAs($student)
            ->post(route('feedback.store'), [
                'category_id' => $category->id,
                'content' => 'The computer laboratory is clean and comfortable.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('feedbacks', [
            'submitter_hash' => FeedbackSubmitter::hashFor($student),
            'category_id' => $category->id,
        ]);
    }

    public function test_user_can_view_only_their_own_feedback_and_analysis(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $category = Category::create([
            'name' => 'CCIS',
            'slug' => 'ccis',
            'is_active' => true,
        ]);

        $ownFeedback = Feedback::create([
            'submitter_hash' => FeedbackSubmitter::hashFor($student),
            'category_id' => $category->id,
            'content' => 'The Wi-Fi connection is reliable today.',
            'status' => 'analyzed',
        ]);
        SentimentResult::create([
            'feedback_id' => $ownFeedback->id,
            'sentiment' => 'positive',
            'confidence' => .91,
            'keywords' => ['wifi', 'reliable'],
            'language_category' => 'English',
        ]);

        Feedback::create([
            'submitter_hash' => FeedbackSubmitter::hashFor($otherStudent),
            'category_id' => $category->id,
            'content' => 'This belongs to another student.',
            'status' => 'pending',
        ]);

        $this->actingAs($student)
            ->get(route('feedback.history'))
            ->assertOk()
            ->assertSee('My Feedback History')
            ->assertSee('The Wi-Fi connection is reliable today.')
            ->assertSee('Positive')
            ->assertSee('91% confidence')
            ->assertSee('English')
            ->assertSee('#wifi')
            ->assertDontSee('This belongs to another student.');
    }

    public function test_feedback_history_requires_authentication(): void
    {
        $this->get(route('feedback.history'))->assertRedirect(route('login'));
    }
}
