<?php

namespace Tests\Feature\Playtest;

use Tests\Feature\Playtest\Utility\UtilityStrategy;

/** Rule list for the profile's engine (spec 4: both engines selectable until S9). */
final class BotEngine
{
    /** @return list<array{name:string, when:callable, do:callable}> */
    public static function rules(BotProfile $profile): array
    {
        return match ($profile->engine) {
            'legacy' => BotStrategy::default($profile),
            'utility' => UtilityStrategy::rules($profile),
        };
    }
}
