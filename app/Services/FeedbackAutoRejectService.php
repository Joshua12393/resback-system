<?php

namespace App\Services;

use Illuminate\Support\Str;

class FeedbackAutoRejectService
{
    public function shouldReject(string $content): bool
    {
        $normalizedContent = $this->normalize($content);

        foreach ($this->blockedEntries() as $blockedEntry) {
            if (! is_string($blockedEntry)) {
                continue;
            }

            $normalizedEntry = $this->normalize($blockedEntry);
            if ($normalizedEntry === '') {
                continue;
            }

            $pattern = '/(?<![\pL\pN])'.preg_quote($normalizedEntry, '/').'(?![\pL\pN])/u';

            if (preg_match($pattern, $normalizedContent) === 1) {
                return true;
            }
        }

        return false;
    }

    /** @return list<mixed> */
    private function blockedEntries(): array
    {
        $entries = config('feedback.auto_reject_feedback', []);
        $entries = is_array($entries) ? array_values($entries) : [];
        $variantGroups = config('feedback.auto_reject_variants', []);

        if (! is_array($variantGroups)) {
            return $entries;
        }

        foreach ($variantGroups as $variants) {
            if (is_array($variants)) {
                array_push($entries, ...array_values($variants));
            }
        }

        return $entries;
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::squish($value));
    }
}
