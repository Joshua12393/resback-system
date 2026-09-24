<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\User;
use App\Services\FeedbackAutoRejectService;
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

    public function test_configured_malicious_phrase_is_saved_as_rejected_without_api_analysis(): void
    {
        config()->set('feedback.auto_reject_feedback', ['malicious test phrase']);
        config()->set('feedback.auto_reject_variants', []);

        $student = User::factory()->create(['role' => 'student']);
        $category = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        $this->mock(SentimentService::class)->shouldNotReceive('analyze');

        $this->actingAs($student)
            ->post(route('feedback.store'), [
                'category_id' => $category->id,
                'content' => 'This contains a MALICIOUS TEST PHRASE in the submission.',
            ])
            ->assertOk()
            ->assertSee('Feedback Rejected')
            ->assertSee('not included in faculty analytics');

        $feedback = Feedback::query()->sole();

        $this->assertSame('rejected', $feedback->status);
        $this->assertNull($feedback->sentimentResult);
    }

    public function test_rejection_dictionary_matches_whole_words_instead_of_partial_words(): void
    {
        config()->set('feedback.auto_reject_feedback', ['spam']);
        config()->set('feedback.auto_reject_variants', []);

        $student = User::factory()->create(['role' => 'student']);
        $category = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        $this->mock(SentimentService::class)->shouldReceive('analyze')->once();

        $this->actingAs($student)
            ->post(route('feedback.store'), [
                'category_id' => $category->id,
                'content' => 'The spammer keeps sending unwanted classroom messages.',
            ])
            ->assertOk()
            ->assertDontSee('Feedback Rejected');

        $this->assertDatabaseHas('feedbacks', ['status' => 'pending']);
    }

    public function test_curated_misspelling_variant_is_rejected(): void
    {
        config()->set('feedback.auto_reject_feedback', ['tanga']);
        config()->set('feedback.auto_reject_variants', [
            'tanga' => ['tnga'],
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $category = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        $this->mock(SentimentService::class)->shouldNotReceive('analyze');

        $this->actingAs($student)
            ->post(route('feedback.store'), [
                'category_id' => $category->id,
                'content' => 'Tnga kayo at wala itong silbi.',
            ])
            ->assertOk()
            ->assertSee('Feedback Rejected');

        $this->assertDatabaseHas('feedbacks', ['status' => 'rejected']);
    }

    public function test_configured_ilocano_variant_is_rejected(): void
    {
        config()->set('feedback.auto_reject_feedback', ['ukinna']);
        config()->set('feedback.auto_reject_variants', [
            'ukinna' => ['ukinnayo'],
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $category = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        $this->mock(SentimentService::class)->shouldNotReceive('analyze');

        $this->actingAs($student)
            ->post(route('feedback.store'), [
                'category_id' => $category->id,
                'content' => 'Ukinnayo amin, awan ti serbi na.',
            ])
            ->assertOk()
            ->assertSee('Feedback Rejected');

        $this->assertDatabaseHas('feedbacks', ['status' => 'rejected']);
    }

    public function test_requested_ilocano_terms_are_detected_without_matching_tagalog_fall_form(): void
    {
        config()->set('feedback.auto_reject_feedback', ['tanga', 'laglag']);
        config()->set('feedback.auto_reject_variants', [
            'tanga' => ['nagtanga'],
        ]);

        $detector = app(FeedbackAutoRejectService::class);

        $this->assertTrue($detector->shouldReject('Nagtanga ka met.'));
        $this->assertTrue($detector->shouldReject('Laglag ka unay.'));
        $this->assertFalse($detector->shouldReject('Nalalaglag ang bahagi ng kisame sa room.'));
    }

    public function test_common_symbol_substitutions_and_repeated_letters_are_detected(): void
    {
        config()->set('feedback.auto_reject_feedback', ['tanga', 'shit', 'bobo']);
        config()->set('feedback.auto_reject_variants', []);
        config()->set('feedback.obfuscation', [
            'strip_inner_symbols' => true,
            'collapse_repeated_characters' => true,
            'symbol_substitutions' => [
                '@' => 'a',
                '0' => 'o',
                '!' => 'i',
            ],
        ]);

        $detector = app(FeedbackAutoRejectService::class);

        $this->assertTrue($detector->shouldReject('T@nga ang gumawa nito.'));
        $this->assertTrue($detector->shouldReject('This is sh!t.'));
        $this->assertTrue($detector->shouldReject('Boooobooo kayo.'));
    }

    public function test_inner_punctuation_is_removed_without_enabling_partial_word_matches(): void
    {
        config()->set('feedback.auto_reject_feedback', ['gago', 'spam']);
        config()->set('feedback.auto_reject_variants', []);

        $detector = app(FeedbackAutoRejectService::class);

        $this->assertTrue($detector->shouldReject('G.a.g.o ka.'));
        $this->assertFalse($detector->shouldReject('The spammer sent another message.'));
    }

    public function test_default_dictionary_detects_configured_leetspeak_and_phonetic_forms(): void
    {
        $detector = app(FeedbackAutoRejectService::class);

        $this->assertTrue($detector->shouldReject('T4nga ang gumawa nito.'));
        $this->assertTrue($detector->shouldReject('A$$hole ang tawag niya.'));
        $this->assertTrue($detector->shouldReject('P3kp3k ang isinulat niya.'));
        $this->assertTrue($detector->shouldReject('Uulooll kayo.'));
        $this->assertTrue($detector->shouldReject('Dafuq is this?'));
    }

    public function test_two_letter_dictionary_entries_are_ignored_to_protect_initials_and_course_codes(): void
    {
        config()->set('feedback.auto_reject_feedback', ['bs']);
        config()->set('feedback.auto_reject_variants', []);

        $detector = app(FeedbackAutoRejectService::class);

        $this->assertFalse($detector->shouldReject('The BS Information Technology curriculum needs review.'));
    }
}
