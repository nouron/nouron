<?php

namespace Tests\Feature\Playtest\Utility;

final readonly class Decision
{
    /**
     * @param  list<array{name:string, key:string, band:int, score:?int}>  $alternatives  next best (max 3)
     * @param  list<Candidate>  $all  every candidate collected for this decision
     */
    public function __construct(
        public Candidate $chosen,
        public int $candidatesN,
        public array $alternatives,
        public array $all = [],
    ) {}
}
