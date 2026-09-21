<?php

namespace App\Services;

use App\Models\Category;
use App\Models\ConcernRanking;
use App\Models\SentimentResult;
use App\Support\FeedbackDateRange;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConcernRankingService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rank(
        ?int $categoryId = null,
        ?string $languageCategory = null,
        ?FeedbackDateRange $dateRange = null,
    ): Collection {
        $dateRange ??= new FeedbackDateRange(null, null);

        $results = SentimentResult::query()
            ->with('feedback')
            ->whereHas('feedback', fn ($query) => $dateRange->apply(
                $query->when($categoryId, fn ($feedbackQuery) => $feedbackQuery->where('category_id', $categoryId))
            ))
            ->when($languageCategory, fn ($query) => $query->where('language_category', $languageCategory))
            ->get();

        $topics = [];

        foreach ($results as $result) {
            if (! $result->feedback) {
                continue;
            }

            foreach ($result->concern_topics ?? ['Other Concern'] as $topic) {
                $topics[$topic] ??= [
                    'topic' => $topic,
                    'total_count' => 0,
                    'positive_count' => 0,
                    'neutral_count' => 0,
                    'negative_count' => 0,
                    'negative_confidences' => [],
                    'negative_recency' => [],
                    'latest_feedback_at' => null,
                    'examples' => [],
                ];

                $item = &$topics[$topic];
                $item['total_count']++;
                $item[$result->sentiment.'_count']++;

                $submittedAt = $result->feedback->created_at;
                if ($item['latest_feedback_at'] === null || $submittedAt->gt($item['latest_feedback_at'])) {
                    $item['latest_feedback_at'] = $submittedAt;
                }

                if ($result->sentiment === 'negative') {
                    $ageDays = max(0, now()->diffInDays($submittedAt, true));
                    $item['negative_confidences'][] = (float) $result->confidence;
                    $item['negative_recency'][] = exp(-log(2) * $ageDays / 30);
                    $item['examples'][] = [
                        'content' => $result->feedback->content,
                        'submitted_at' => $submittedAt,
                    ];
                }

                unset($item);
            }
        }

        $negativeCounts = array_values(array_map(
            fn (array $topic): int => $topic['negative_count'],
            $topics
        ));
        $maxNegativeCount = max([1, ...$negativeCounts]);

        return collect($topics)
            ->map(function (array $topic) use ($maxNegativeCount): array {
                $negativeRatio = $topic['total_count'] > 0
                    ? $topic['negative_count'] / $topic['total_count']
                    : 0;
                $averageConfidence = $this->average($topic['negative_confidences']);
                $recencyScore = $this->average($topic['negative_recency']);
                $volumeScore = $topic['negative_count'] > 0
                    ? log(1 + $topic['negative_count']) / log(1 + $maxNegativeCount)
                    : 0;

                $topic['critical_score'] = round(100 * (
                    .40 * $volumeScore
                    + .25 * $negativeRatio
                    + .20 * $averageConfidence
                    + .15 * $recencyScore
                ), 1);
                $topic['negative_ratio'] = round($negativeRatio, 4);
                $topic['average_negative_confidence'] = round($averageConfidence, 4);
                $topic['recency_score'] = round($recencyScore, 4);
                $topic['low_evidence'] = $topic['total_count'] < 3;
                $topic['examples'] = collect($topic['examples'])
                    ->sortByDesc('submitted_at')
                    ->take(3)
                    ->values()
                    ->all();

                unset($topic['negative_confidences'], $topic['negative_recency']);

                return $topic;
            })
            ->sortBy([
                ['critical_score', 'desc'],
                ['negative_count', 'desc'],
                ['topic', 'asc'],
            ])
            ->values();
    }

    public function rebuildCategory(int $categoryId): void
    {
        $rankings = $this->rank($categoryId);

        DB::transaction(function () use ($categoryId, $rankings): void {
            ConcernRanking::query()->where('category_id', $categoryId)->delete();

            foreach ($rankings as $ranking) {
                ConcernRanking::create([
                    'category_id' => $categoryId,
                    'keyword' => $ranking['topic'],
                    'frequency' => $ranking['total_count'],
                    'positive_count' => $ranking['positive_count'],
                    'neutral_count' => $ranking['neutral_count'],
                    'negative_count' => $ranking['negative_count'],
                    'critical_score' => $ranking['critical_score'],
                    'negative_ratio' => $ranking['negative_ratio'],
                    'average_negative_confidence' => $ranking['average_negative_confidence'],
                    'recency_score' => $ranking['recency_score'],
                    'latest_feedback_at' => $ranking['latest_feedback_at'],
                    'ranked_at' => now(),
                ]);
            }
        });
    }

    public function rebuildAll(): void
    {
        Category::query()->whereHas('feedbacks')->pluck('id')->each(
            fn (int $categoryId) => $this->rebuildCategory($categoryId)
        );
    }

    /** @param list<float> $values */
    private function average(array $values): float
    {
        return $values === [] ? 0 : array_sum($values) / count($values);
    }
}
