<?php

namespace Tests\Feature;

use App\Models\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FeedbackResultViewTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('sentimentResults')]
    public function test_submission_result_displays_the_actual_sentiment_percentage(
        string $sentiment,
        float $confidence,
        string $expectedSummary,
    ): void
    {
        $feedback = Feedback::create([
            'content' => 'The Wi-Fi connection is reliable today.',
            'status' => 'analyzed',
        ]);
        $feedback->sentimentResult()->create([
            'sentiment' => $sentiment,
            'confidence' => $confidence,
            'keywords' => ['wifi', 'service'],
            'concern_topics' => ['Wi-Fi / Internet'],
            'detected_languages' => ['English'],
            'language_category' => 'English',
            'language_confidence' => 1,
        ]);

        $this->view('feedback.result', ['feedback' => $feedback->load('sentimentResult')])
            ->assertSee($expectedSummary)
            ->assertSee('#wifi')
            ->assertSee('#service')
            ->assertSee('Language')
            ->assertSee('English')
            ->assertSee('100% language confidence')
            ->assertDontSee('Google Gemini API')
            ->assertSee($feedback->content);
    }

    public function test_failed_analysis_still_displays_feedback_without_invented_results(): void
    {
        $feedback = Feedback::create([
            'content' => 'The laboratory needs more working computers.',
            'status' => 'failed',
        ]);

        $this->view('feedback.result', ['feedback' => $feedback])
            ->assertSee($feedback->content)
            ->assertSee('analysis could not be completed')
            ->assertDontSee('Sentiment confidence');
    }

    public function test_rejected_feedback_is_displayed_safely_without_analysis(): void
    {
        $feedback = Feedback::create([
            'content' => '<script>alert("unsafe")</script> This feedback was rejected.',
            'status' => 'rejected',
        ]);

        $this->view('feedback.result', ['feedback' => $feedback])
            ->assertSee('Feedback Rejected')
            ->assertSee($feedback->content)
            ->assertDontSee('<script>alert("unsafe")</script>', false)
            ->assertDontSee('Sentiment confidence');
    }

    /** @return array<string, array{string, float, string}> */
    public static function sentimentResults(): array
    {
        return [
            'positive result' => ['positive', .842, '84% Positive'],
            'neutral result' => ['neutral', .506, '51% Neutral'],
            'negative result' => ['negative', .913, '91% Negative'],
        ];
    }
}
