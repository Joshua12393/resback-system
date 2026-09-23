<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_can_view_dashboard_and_export_action(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);

        $this->actingAs($faculty)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Export Feedbacks (.xlsx)')
            ->assertSee('Quick Actions')
            ->assertSee('View Feedback Form');
    }

    public function test_admin_dashboard_does_not_show_feedback_quick_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Quick Actions')
            ->assertDontSee('View Feedback Form')
            ->assertSee('Manage Accounts');

        $this->actingAs($admin)
            ->get(route('feedback.create'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Administrators cannot submit feedback.');
        $this->actingAs($admin)->post(route('feedback.store'))->assertForbidden();
    }

    public function test_dashboard_defaults_to_ccis_and_excludes_other_categories(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $ccis = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        $cas = Category::create(['name' => 'CAS', 'slug' => 'cas', 'is_active' => true]);

        foreach (range(1, 12) as $number) {
            Feedback::create([
                'category_id' => $number % 2 ? $ccis->id : $cas->id,
                'content' => "Mixed dashboard feedback {$number}",
                'status' => 'pending',
            ]);
        }

        $this->actingAs($faculty)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('CCIS')
            ->assertDontSee('CAS — Coming soon')
            ->assertViewHas('totalFeedbacks', 6)
            ->assertViewHas('selectedCategory', fn ($category) => $category->is($ccis))
            ->assertViewHas('recentFeedbacks', fn ($feedbacks) => $feedbacks->total() === 6
                && collect($feedbacks->items())->every(fn ($feedback) => $feedback->category_id === $ccis->id)
            );
    }

    public function test_dashboard_rejects_a_non_ccis_category_filter(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        $cas = Category::create(['name' => 'CAS', 'slug' => 'cas', 'is_active' => true]);

        $this->actingAs($faculty)
            ->get(route('dashboard', ['category_id' => $cas->id]))
            ->assertNotFound();
    }

    public function test_category_selection_filters_every_dashboard_result(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $ccis = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        $cas = Category::create(['name' => 'CAS', 'slug' => 'cas', 'is_active' => true]);

        foreach (range(1, 12) as $number) {
            Feedback::create([
                'category_id' => $ccis->id,
                'content' => "CCIS-only feedback {$number}",
                'status' => 'pending',
            ]);
        }

        Feedback::create([
            'category_id' => $cas->id,
            'content' => 'CAS feedback must not appear',
            'status' => 'pending',
        ]);

        $this->actingAs($faculty)
            ->get(route('dashboard', ['category_id' => $ccis->id]))
            ->assertOk()
            ->assertSee('Only CCIS')
            ->assertDontSee('CAS feedback must not appear')
            ->assertViewHas('totalFeedbacks', 12)
            ->assertViewHas('selectedCategory', fn ($category) => $category->is($ccis))
            ->assertViewHas('recentFeedbacks', fn ($feedbacks) => $feedbacks->total() === 12
                && $feedbacks->count() === 10
                && collect($feedbacks->items())->every(fn ($feedback) => $feedback->category_id === $ccis->id)
            );
    }

    public function test_category_and_language_filters_can_be_combined(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $ccis = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        $cas = Category::create(['name' => 'CAS', 'slug' => 'cas', 'is_active' => true]);

        foreach (range(1, 11) as $number) {
            $feedback = Feedback::create([
                'category_id' => $ccis->id,
                'content' => "CCIS Taglish feedback {$number}",
                'status' => 'analyzed',
            ]);
            $feedback->sentimentResult()->create([
                'sentiment' => 'positive',
                'confidence' => .9,
                'language_category' => 'Taglish',
            ]);
        }

        $englishFeedback = Feedback::create([
            'category_id' => $ccis->id,
            'content' => 'CCIS English feedback must not appear',
            'status' => 'analyzed',
        ]);
        $englishFeedback->sentimentResult()->create([
            'sentiment' => 'neutral',
            'confidence' => .8,
            'language_category' => 'English',
        ]);

        $otherCategoryFeedback = Feedback::create([
            'category_id' => $cas->id,
            'content' => 'CAS Taglish feedback must not appear',
            'status' => 'analyzed',
        ]);
        $otherCategoryFeedback->sentimentResult()->create([
            'sentiment' => 'negative',
            'confidence' => .8,
            'language_category' => 'Taglish',
        ]);

        $this->actingAs($faculty)
            ->get(route('dashboard', [
                'category_id' => $ccis->id,
                'language_category' => 'Taglish',
            ]))
            ->assertOk()
            ->assertSee('CCIS · Taglish')
            ->assertDontSee('CCIS English feedback must not appear')
            ->assertDontSee('CAS Taglish feedback must not appear')
            ->assertViewHas('totalFeedbacks', 11)
            ->assertViewHas('sentimentData', [
                'positive' => 11,
                'neutral' => 0,
                'negative' => 0,
            ])
            ->assertViewHas('recentFeedbacks', fn ($feedbacks) => $feedbacks->total() === 11 && $feedbacks->count() === 10
            );
    }

    public function test_long_feedback_can_be_expanded_to_read_the_complete_message(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $category = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);
        $longFeedback = str_repeat('This is an important and detailed student concern. ', 8).'Complete ending marker.';

        Feedback::create([
            'category_id' => $category->id,
            'content' => $longFeedback,
            'status' => 'pending',
        ]);

        $this->actingAs($faculty)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Read full feedback')
            ->assertSee($longFeedback);
    }

    public function test_dashboard_date_range_filters_statistics_charts_rankings_and_feedbacks(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $category = Category::create(['name' => 'CCIS', 'slug' => 'ccis', 'is_active' => true]);

        $insideRange = Feedback::create([
            'category_id' => $category->id,
            'content' => 'Wi-Fi concern inside selected period',
            'status' => 'analyzed',
        ]);
        $insideRange->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();
        $insideRange->sentimentResult()->create([
            'sentiment' => 'negative',
            'confidence' => .92,
            'concern_topics' => ['Wi-Fi / Internet'],
            'language_category' => 'English',
        ]);

        $outsideRange = Feedback::create([
            'category_id' => $category->id,
            'content' => 'Room concern outside selected period',
            'status' => 'analyzed',
        ]);
        $outsideRange->forceFill(['created_at' => now()->subDays(20)])->saveQuietly();
        $outsideRange->sentimentResult()->create([
            'sentiment' => 'positive',
            'confidence' => .88,
            'concern_topics' => ['Classroom / Room'],
            'language_category' => 'English',
        ]);

        $this->actingAs($faculty)
            ->get(route('dashboard', [
                'start_date' => now()->subDays(5)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]))
            ->assertOk()
            ->assertSee('Wi-Fi concern inside selected period')
            ->assertDontSee('Room concern outside selected period')
            ->assertSee('Download PDF Report')
            ->assertViewHas('totalFeedbacks', 1)
            ->assertViewHas('sentimentData', [
                'positive' => 0,
                'neutral' => 0,
                'negative' => 1,
            ])
            ->assertViewHas('concernRankings', fn ($rankings) => $rankings->first()['topic'] === 'Wi-Fi / Internet');
    }

    public function test_dashboard_rejects_an_inverted_date_range(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);

        $this->actingAs($faculty)
            ->from(route('dashboard'))
            ->get(route('dashboard', [
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->subDay()->format('Y-m-d'),
            ]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('end_date');
    }

    public function test_feedback_pagination_ajax_returns_only_the_feedback_panel(): void
    {
        $faculty = User::factory()->create(['role' => 'faculty']);
        $category = Category::ccis();

        foreach (range(1, 11) as $number) {
            Feedback::create([
                'category_id' => $category->id,
                'content' => "Paginated feedback {$number}",
                'status' => 'pending',
            ]);
        }

        $this->actingAs($faculty)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'X-Feedback-Partial' => '1',
            ])
            ->get(route('dashboard', ['page' => 2]))
            ->assertOk()
            ->assertViewIs('dashboard.partials.feedback-table')
            ->assertSee('id="ccisFeedbackPanel"', false)
            ->assertSee('Paginated feedback 1')
            ->assertDontSee('sentimentDistributionChart');
    }
}
