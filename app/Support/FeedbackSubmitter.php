<?php

namespace App\Support;

use App\Models\User;
use RuntimeException;

/**
 * Pseudonymous link between an account and its feedback.
 *
 * Feedback stores only a keyed HMAC of the submitter's account ID, never the ID
 * itself, so feedback rows cannot be joined to accounts. The app can still
 * recompute the hash to show a student their own history and to stop
 * duplicate submissions. Changing the key breaks that link for old feedback.
 */
final class FeedbackSubmitter
{
    public static function hashFor(User|int $user): string
    {
        $id = $user instanceof User ? $user->getKey() : $user;

        return hash_hmac('sha256', 'feedback-submitter:'.$id, self::key());
    }

    private static function key(): string
    {
        $key = (string) config('feedback.submitter_key');

        if ($key === '') {
            throw new RuntimeException('FEEDBACK_SUBMITTER_KEY (or APP_KEY) must be set.');
        }

        return $key;
    }
}
