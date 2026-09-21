<?php

namespace App\Services;

use Illuminate\Support\Str;

class ConcernTopicService
{
    public const TOPICS = [
        'Wi-Fi / Internet',
        'Classroom / Room',
        'Facilities',
        'Cleanliness',
        'Teaching',
        'Schedule',
        'Enrollment',
        'Library',
        'Safety',
        'Equipment',
        'Administration',
        'Other Concern',
    ];

    private const ALIASES = [
        'Wi-Fi / Internet' => ['wifi', 'wi-fi', 'internet', 'connection', 'connectivity', 'network', 'signal', 'router', 'bandwidth'],
        'Classroom / Room' => ['classroom', 'class room', 'room', 'silid', 'kuwarto', 'kwarto', 'lecture room'],
        'Facilities' => ['facility', 'facilities', 'building', 'restroom', 'comfort room', 'cr', 'toilet', 'bathroom', 'washroom', 'lavatory', 'kasilyas', 'canteen', 'covered court', 'oval'],
        'Cleanliness' => ['clean', 'cleanliness', 'dirty', 'garbage', 'trash', 'basura', 'dumi', 'marumi', 'nadalus', 'narugit', 'mabaho', 'smell', 'smelly', 'stink', 'stinks', 'stinky', 'odor', 'odour', 'bangsit', 'nabangsit'],
        'Teaching' => ['teaching', 'teacher', 'instructor', 'professor', 'lesson', 'lecture', 'guro', 'maestro', 'faculty'],
        'Schedule' => ['schedule', 'timetable', 'deadline', 'oras', 'iskedyul', 'sched'],
        'Enrollment' => ['enrollment', 'enrolment', 'registration', 'register', 'admission'],
        'Library' => ['library', 'librarian', 'book', 'books', 'aklat'],
        'Safety' => ['safety', 'unsafe', 'danger', 'security', 'hazard', 'accident', 'ligtas', 'delikado'],
        'Equipment' => ['equipment', 'computer', 'projector', 'laboratory', 'lab', 'printer', 'device', 'machine'],
        'Administration' => ['administration', 'admin', 'office', 'policy', 'process', 'staff', 'service'],
    ];

    private const RESTROOM_ALIASES = [
        'restroom', 'comfort room', 'cr', 'toilet', 'bathroom', 'washroom', 'lavatory', 'kasilyas',
    ];

    private const STRONG_TEACHING_ALIASES = [
        'teaching', 'lesson', 'lecture', 'instruction', 'curriculum', 'grading', 'grade', 'exam', 'discussion',
    ];

    /**
     * @param array<mixed> $topics
     * @param array<mixed> $keywords
     * @return list<string>
     */
    public function normalize(array $topics, array $keywords = [], string $content = ''): array
    {
        $normalized = collect($topics)
            ->filter(fn (mixed $topic): bool => is_string($topic) && filled(trim($topic)))
            ->map(fn (string $topic): ?string => $this->canonicalTopic($topic))
            ->filter()
            ->unique()
            ->values();

        $haystack = collect($keywords)
            ->filter(fn (mixed $keyword): bool => is_string($keyword))
            ->push($content)
            ->implode(' ');

        foreach (self::ALIASES as $topic => $aliases) {
            if ($this->containsAlias($haystack, $aliases)) {
                $normalized->push($topic);
            }
        }

        if ($this->containsAlias($haystack, self::RESTROOM_ALIASES)
            && ! $this->containsAlias($haystack, self::STRONG_TEACHING_ALIASES)) {
            $normalized = $normalized->reject(fn (string $topic): bool => $topic === 'Teaching');
        }

        $normalized = $normalized
            ->unique()
            ->reject(fn (string $topic): bool => $topic === 'Other Concern' && $normalized->count() > 1)
            ->take(3)
            ->values();

        return $normalized->isEmpty() ? ['Other Concern'] : $normalized->all();
    }

    private function canonicalTopic(string $topic): ?string
    {
        $topic = Str::lower(trim($topic));

        foreach (self::TOPICS as $canonical) {
            if ($topic === Str::lower($canonical)) {
                return $canonical;
            }
        }

        foreach (self::ALIASES as $canonical => $aliases) {
            if ($this->containsAlias($topic, $aliases)) {
                return $canonical;
            }
        }

        return null;
    }

    /** @param list<string> $aliases */
    private function containsAlias(string $text, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            $pattern = '/(?<![\pL\pN])'.preg_quote($alias, '/').'(?![\pL\pN])/iu';
            if (preg_match($pattern, $text) === 1) {
                return true;
            }
        }

        return false;
    }
}
