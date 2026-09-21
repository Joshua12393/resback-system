<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Feedback;
use App\Models\SentimentResult;
use App\Services\ConcernRankingService;
use App\Services\LanguageCategoryService;
use App\Support\FeedbackDateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the admin/faculty dashboard with concern rankings and sentiment data.
     */
    public function index(Request $request, ConcernRankingService $rankingService): View
    {
        $dateRange = FeedbackDateRange::fromRequest($request);
        $selectedCategory = Category::ccis();
        if ($request->filled('category_id')) {
            abort_unless($request->integer('category_id') === $selectedCategory->id, 404, 'The selected category is unavailable.');
        }

        $filterLanguages = LanguageCategoryService::CATEGORY_LABELS;
        $selectedLanguage = null;
        if ($request->filled('language_category')) {
            $selectedLanguage = $request->string('language_category')->toString();
            abort_unless(in_array($selectedLanguage, $filterLanguages, true), 404, 'The selected language is unavailable.');
        }

        $hasFilters = $selectedCategory !== null || $selectedLanguage !== null || $dateRange->isActive();

        $feedbackScope = $dateRange->apply(Feedback::query()
            ->reportable()
            ->when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))
            ->when($selectedLanguage, fn ($query) => $query->whereHas(
                'sentimentResult',
                fn ($resultQuery) => $resultQuery->where('language_category', $selectedLanguage)
            )));

        // --- Summary Stats ---
        $totalFeedbacks = (clone $feedbackScope)->count();
        $analyzedCount = (clone $feedbackScope)->where('status', 'analyzed')->count();
        $pendingCount = (clone $feedbackScope)->where('status', 'pending')->count();

        // --- Sentiment Distribution ---
        $sentimentCounts = SentimentResult::query()
            ->whereHas('feedback', fn ($feedbackQuery) => $dateRange->apply(
                $feedbackQuery->when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))
            ))
            ->when($selectedLanguage, fn ($query) => $query->where('language_category', $selectedLanguage))
            ->selectRaw('sentiment, COUNT(*) as count')
            ->groupBy('sentiment')
            ->pluck('count', 'sentiment')
            ->toArray();

        $sentimentData = [
            'positive' => $sentimentCounts['positive'] ?? 0,
            'neutral' => $sentimentCounts['neutral'] ?? 0,
            'negative' => $sentimentCounts['negative'] ?? 0,
        ];

        // --- Language Distribution ---
        $languageData = SentimentResult::query()
            ->whereHas('feedback', fn ($feedbackQuery) => $dateRange->apply(
                $feedbackQuery->when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))
            ))
            ->when($selectedLanguage, fn ($query) => $query->where('language_category', $selectedLanguage))
            ->whereNotNull('language_category')
            ->selectRaw('language_category, COUNT(*) as count')
            ->groupBy('language_category')
            ->orderByDesc('count')
            ->pluck('count', 'language_category');

        $concernRankings = $rankingService
            ->rank($selectedCategory?->id, $selectedLanguage, $dateRange)
            ->take(10)
            ->values();

        $sentimentChartData = [
            'labels' => ['Positive', 'Neutral', 'Negative'],
            'values' => [
                $sentimentData['positive'],
                $sentimentData['neutral'],
                $sentimentData['negative'],
            ],
        ];

        $concernChartData = [
            'labels' => $concernRankings->pluck('topic')->all(),
            'values' => $concernRankings->pluck('critical_score')->all(),
        ];

        $sentimentTrendData = $this->buildSentimentTrend($selectedCategory?->id, $selectedLanguage, $dateRange);
        $trendLabel = $dateRange->isActive() ? $dateRange->label() : 'Last 30 days';

        // The mixed overview is capped at 10. A category view is paginated.
        $recentFeedbackQuery = $dateRange->apply(Feedback::with(['category', 'sentimentResult'])
            ->reportable()
            ->when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))
            ->when($selectedLanguage, fn ($query) => $query->whereHas(
                'sentimentResult',
                fn ($resultQuery) => $resultQuery->where('language_category', $selectedLanguage)
            )))
            ->latest()
            ->latest('id');

        $recentFeedbacks = $hasFilters
            ? $recentFeedbackQuery->paginate(10)->withQueryString()
            : $recentFeedbackQuery->limit(10)->get();

        $filterLabel = collect([
            $selectedCategory?->name,
            $selectedLanguage,
            $dateRange->isActive() ? $dateRange->label() : null,
        ])
            ->filter()
            ->implode(' · ');

        if ($request->ajax() && $request->header('X-Feedback-Partial') === '1') {
            return view('dashboard.partials.feedback-table', compact(
                'recentFeedbacks',
                'hasFilters',
                'filterLabel'
            ));
        }

        $reportQuery = array_filter([
            'language_category' => $selectedLanguage,
            ...$dateRange->queryParameters(),
        ], fn ($value) => $value !== null && $value !== '');

        return view('dashboard.index', compact(
            'totalFeedbacks',
            'analyzedCount',
            'pendingCount',
            'sentimentData',
            'languageData',
            'recentFeedbacks',
            'selectedCategory',
            'filterLanguages',
            'selectedLanguage',
            'hasFilters',
            'filterLabel',
            'concernRankings',
            'sentimentChartData',
            'concernChartData',
            'sentimentTrendData',
            'dateRange',
            'trendLabel',
            'reportQuery'
        ));
    }

    /** @return array{labels: list<string>, positive: list<int>, negative: list<int>} */
    private function buildSentimentTrend(
        ?int $categoryId,
        ?string $languageCategory,
        FeedbackDateRange $dateRange,
    ): array {
        $startDate = $dateRange->start ?? now()->startOfDay()->subDays(29);
        $endDate = $dateRange->end ?? now()->endOfDay();
        $dayCount = (int) $startDate->copy()->startOfDay()->diffInDays($endDate->copy()->startOfDay());
        $days = collect(range(0, $dayCount))->mapWithKeys(function (int $offset) use ($startDate): array {
            $date = $startDate->copy()->addDays($offset);

            return [$date->format('Y-m-d') => [
                'label' => $date->format('M j'),
                'positive' => 0,
                'negative' => 0,
            ]];
        });

        SentimentResult::query()
            ->with('feedback')
            ->whereIn('sentiment', ['positive', 'negative'])
            ->when($languageCategory, fn ($query) => $query->where('language_category', $languageCategory))
            ->whereHas('feedback', fn ($query) => $query
                ->whereBetween('created_at', [$startDate, $endDate])
                ->when($categoryId, fn ($feedbackQuery) => $feedbackQuery->where('category_id', $categoryId)))
            ->get()
            ->each(function (SentimentResult $result) use ($days): void {
                $date = $result->feedback?->created_at?->format('Y-m-d');
                if ($date && $days->has($date)) {
                    $day = $days->get($date);
                    $day[$result->sentiment]++;
                    $days->put($date, $day);
                }
            });

        return [
            'labels' => $days->pluck('label')->values()->all(),
            'positive' => $days->pluck('positive')->values()->all(),
            'negative' => $days->pluck('negative')->values()->all(),
        ];
    }
}
