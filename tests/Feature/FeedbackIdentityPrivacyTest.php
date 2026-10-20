<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Feedback is confidential: staff see the content, never who submitted it.
 */
class FeedbackIdentityPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'HiddenStudent_77',
            'nickname' => 'HiddenStudent_77',
            'email' => 'hidden.student@example.test',
        ]);

        $category = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);

        $feedback = Feedback::create([
            'user_id' => $this->student->id,
            'category_id' => $category->id,
            'content' => 'The laboratory wifi keeps disconnecting.',
            'status' => 'analyzed',
        ]);

        $feedback->sentimentResult()->create([
            'sentiment' => 'negative',
            'confidence' => 0.9,
            'keywords' => ['wifi'],
            'language_category' => 'English',
        ]);
    }

    public function test_dashboard_shows_feedback_without_student_identity(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('The laboratory wifi keeps disconnecting.')
            ->assertDontSee('HiddenStudent_77')
            ->assertDontSee('hidden.student@example.test');
    }

    public function test_pdf_report_does_not_contain_student_identity(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $reportData = null;

        // The PDF body is compressed, so check the HTML the report is built from.
        View::composer('reports.feedback-pdf', function ($view) use (&$reportData): void {
            $reportData ??= $view->getData();
        });

        $this->actingAs($faculty)->get(route('feedback.report'))->assertOk();

        $this->assertNotNull($reportData);
        $reportHtml = view('reports.feedback-pdf', $reportData)->render();
        $this->assertStringContainsString('The laboratory wifi keeps disconnecting.', $reportHtml);
        $this->assertStringNotContainsString('HiddenStudent_77', $reportHtml);
        $this->assertStringNotContainsString('hidden.student@example.test', $reportHtml);
    }

    public function test_excel_export_does_not_contain_student_identity(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $response = $this->actingAs($faculty)->get(route('feedback.export'))->assertOk();

        $temporaryFile = tempnam(sys_get_temp_dir(), 'resback-privacy-');
        file_put_contents($temporaryFile, $response->streamedContent());

        try {
            $cells = collect(IOFactory::load($temporaryFile)->getActiveSheet()->toArray())
                ->flatten()
                ->filter()
                ->implode(' ');

            $this->assertStringContainsString('The laboratory wifi keeps disconnecting.', $cells);
            $this->assertStringNotContainsString('HiddenStudent_77', $cells);
            $this->assertStringNotContainsString('hidden.student@example.test', $cells);
        } finally {
            @unlink($temporaryFile);
        }
    }
}
