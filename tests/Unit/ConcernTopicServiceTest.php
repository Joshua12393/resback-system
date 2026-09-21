<?php

namespace Tests\Unit;

use App\Services\ConcernTopicService;
use PHPUnit\Framework\TestCase;

class ConcernTopicServiceTest extends TestCase
{
    public function test_it_groups_multilingual_synonyms_into_canonical_topics(): void
    {
        $topics = (new ConcernTopicService())->normalize(
            [],
            ['slow connection'],
            'Nakapsot ti wifi ken marumi ang classroom.'
        );

        $this->assertSame([
            'Wi-Fi / Internet',
            'Classroom / Room',
            'Cleanliness',
        ], $topics);
    }

    public function test_it_uses_other_concern_when_no_known_topic_matches(): void
    {
        $this->assertSame(
            ['Other Concern'],
            (new ConcernTopicService())->normalize([], ['student experience'], 'A unique concern.')
        );
    }

    public function test_restroom_location_reference_is_not_classified_as_teaching(): void
    {
        $topics = (new ConcernTopicService())->normalize(
            ['Teaching'],
            ['teacher', 'cr'],
            "Mabaho ang cr dito sa teacher's CR."
        );

        $this->assertSame(['Facilities', 'Cleanliness'], $topics);
    }

    public function test_restroom_feedback_can_still_include_teaching_when_instruction_is_discussed(): void
    {
        $topics = (new ConcernTopicService())->normalize(
            ['Teaching'],
            ['teacher', 'cr', 'lesson'],
            'The teacher discusses the lesson inside the CR.'
        );

        $this->assertContains('Teaching', $topics);
        $this->assertContains('Facilities', $topics);
    }
}
