<?php

namespace Tests\Feature\Playtest\Utility;

final readonly class Decision
{
    /**
     * @param  list<array{name:string, key:string, band:int, score:?int}>  $alternatives  next best (max 3)
     * @param  list<Candidate>  $all  every candidate collected for this decision
     * @param  ?Candidate  $shadow  band-faithful utility pick (shadow mode, K22)
     * @param  ?Candidate  $shadowFree  cross-band utility pick (informational)
     * @param  list<Candidate>  $shadowTop3  best scored candidates across bands
     * @param  ?Candidate  $chosenEstimate  the legacy choice as scored by the shadow, if scorable
     */
    public function __construct(
        public Candidate $chosen,
        public int $candidatesN,
        public array $alternatives,
        public array $all = [],
        public ?Candidate $shadow = null,
        public ?Candidate $shadowFree = null,
        public array $shadowTop3 = [],
        public ?Candidate $chosenEstimate = null,
    ) {}

    /** @param list<Candidate> $top3 */
    public function withShadow(?Candidate $shadow, ?Candidate $free = null, array $top3 = [], ?Candidate $chosenEstimate = null): self
    {
        return new self($this->chosen, $this->candidatesN, $this->alternatives, $this->all, $shadow, $free, $top3, $chosenEstimate);
    }
}
