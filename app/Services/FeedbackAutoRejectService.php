<?php

namespace App\Services;

use Illuminate\Support\Str;

class FeedbackAutoRejectService
{
    public function shouldReject(string $content): bool
    {
        $contentForms = $this->normalizedForms($content);
        $entryForms = [];
        $minimumLength = max(1, (int) config('feedback.obfuscation.minimum_term_length', 3));

        foreach ($this->blockedEntries() as $blockedEntry) {
            if (! is_string($blockedEntry)) {
                continue;
            }

            foreach ($this->normalizedForms($blockedEntry) as $entryForm) {
                if (mb_strlen(str_replace(' ', '', $entryForm)) < $minimumLength) {
                    continue;
                }

                $entryForms[$entryForm] = true;
            }
        }

        if ($entryForms === []) {
            return false;
        }

        $alternatives = array_keys($entryForms);
        usort($alternatives, fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left));
        $pattern = '/(?<![\pL\pN])(?:'.implode('|', array_map(
            fn (string $entry): string => preg_quote($entry, '/'),
            $alternatives
        )).')(?![\pL\pN])/u';

        foreach ($contentForms as $contentForm) {
            if (preg_match($pattern, $contentForm) === 1) {
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

        return array_values(array_unique($entries, SORT_REGULAR));
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::squish($value));
    }

    /** @return list<string> */
    private function normalizedForms(string $value): array
    {
        $base = $this->normalize($value);
        $forms = [$base];
        $substitutions = config('feedback.obfuscation.symbol_substitutions', []);

        if (is_array($substitutions)) {
            $forms[] = strtr($base, $substitutions);
        }

        $symbolForms = $forms;
        foreach ($symbolForms as $form) {
            if (config('feedback.obfuscation.strip_inner_symbols', true)) {
                $forms[] = preg_replace(
                    '/(?<=[\pL\pN])[^\pL\pN\s]+(?=[\pL\pN])/u',
                    '',
                    $form
                ) ?? $form;
            }
        }

        if (config('feedback.obfuscation.collapse_repeated_characters', true)) {
            $repeatForms = $forms;
            foreach ($repeatForms as $form) {
                $forms[] = preg_replace('/([\pL\pN])\1+/iu', '$1', $form) ?? $form;
            }
        }

        return array_values(array_unique(array_filter($forms, fn (string $form): bool => $form !== '')));
    }
}
