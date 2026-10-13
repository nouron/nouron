<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotSession;

/** Spec 3.1: one producer of candidates; `band` orders sources like the legacy rule list. */
interface CandidateSource
{
    public function name(): string;

    public function band(): int;

    /** @return list<Candidate> */
    public function candidates(BotSession $b, BotContext $ctx): array;
}
