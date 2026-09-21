<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\User;
use App\Services\FeedbackSubmissionGuard;
use App\Services\SentimentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class FeedbackProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_normalized_feedback_cannot_be_resubmitted_within_24_hours(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $category = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        Feedback::create([
            'user_id' => $student->id,
            'category_id' => $category->id,
            'content' => 'The Wi-Fi is very slow today.',
            'status' => 'analyzed',
        ]);

        $this->mock(SentimentService::class)->shouldNotReceive('analyze');

        $this->actingAs($student)
            ->from(route('feedback.create'))
            ->post(route('feedback.store'), [
                'category_id' => $category->id,
                'content' => '  the wi-fi   is very slow today.  ',
            ])
            ->assertRedirect(route('feedback.create'))
            ->assertSessionHasErrors('content');

        $this->assertDatabaseCount('feedbacks', 1);
    }

    public function test_student_is_temporarily_limited_after_five_rapid_submissions(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $category = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        RateLimiter::clear('feedback-submission:'.$student->id);
        $this->mock(SentimentService::class)->shouldReceive('analyze')->times(5);

        foreach (range(1, FeedbackSubmissionGuard::MAX_SUBMISSIONS) as $number) {
            $this->actingAs($student)
                ->post(route('feedback.store'), [
                    'category_id' => $category->id,
                    'content' => "A different legitimate feedback message number {$number}.",
                ])
                ->assertOk();
        }

        $this->actingAs($student)
            ->from(route('feedback.create'))
            ->post(route('feedback.store'), [
                'category_id' => $category->id,
                'content' => 'A sixth rapid feedback submission should be limited.',
            ])
            ->assertRedirect(route('feedback.create'))
            ->assertSessionHasErrors('content');

        $this->assertDatabaseCount('feedbacks', FeedbackSubmissionGuard::MAX_SUBMISSIONS);
    }
}
