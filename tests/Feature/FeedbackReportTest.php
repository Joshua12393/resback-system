<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_can_download_a_date_filtered_pdf_report(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $category = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);

        $feedback = Feedback::create([
            'category_id' => $category->id,
            'content' => 'The laboratory internet connection is unstable.',
            'status' => 'analyzed',
        ]);
        $feedback->forceFill(['created_at' => now()->subDay()])->saveQuietly();
        $feedback->sentimentResult()->create([
            'sentiment' => 'negative',
            'confidence' => .95,
            'keywords' => ['internet', 'unstable'],
            'concern_topics' => ['Wi-Fi / Internet'],
            'detected_languages' => ['English'],
            'language_category' => 'English',
            'language_confidence' => 1,
        ]);

        $oldFeedback = Feedback::create([
            'category_id' => $category->id,
            'content' => 'This older feedback must not affect the selected report.',
            'status' => 'pending',
        ]);
        $oldFeedback->forceFill(['created_at' => now()->subDays(20)])->saveQuietly();

        $response = $this->actingAs($faculty)->get(route('feedback.report', [
            'start_date' => now()->subDays(5)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload(
                'resback-feedback-report-'.now()->subDays(5)->format('Y-m-d').'-to-'.now()->format('Y-m-d').'.pdf'
            );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_guest_cannot_download_pdf_report(): void
    {
        $this->get(route('feedback.report'))->assertRedirect(route('login'));
    }
}
