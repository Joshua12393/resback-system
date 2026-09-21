<?php

namespace App\Services;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class FeedbackSubmissionGuard
{
    public const MAX_SUBMISSIONS = 5;

    public const DECAY_SECONDS = 600;

    public function isDuplicate(User $user, int $categoryId, string $content): bool
    {
        $normalizedContent = $this->normalize($content);

        return $user->feedbacks()
            ->where('category_id', $categoryId)
            ->where('created_at', '>=', now()->subDay())
            ->latest()
            ->get(['content'])
            ->contains(fn (Feedback $feedback) => $this->normalize($feedback->content) === $normalizedContent);
    }

    public function tooManyAttempts(User $user): bool
    {
        return RateLimiter::tooManyAttempts($this->key($user), self::MAX_SUBMISSIONS);
    }

    public function availableIn(User $user): int
    {
        return RateLimiter::availableIn($this->key($user));
    }

    public function recordSubmission(User $user): void
    {
        RateLimiter::hit($this->key($user), self::DECAY_SECONDS);
    }

    private function key(User $user): string
    {
        return 'feedback-submission:'.$user->getKey();
    }

    private function normalize(string $content): string
    {
        return Str::lower(Str::squish($content));
    }
}
