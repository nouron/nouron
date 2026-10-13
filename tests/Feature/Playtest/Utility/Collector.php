<?php

namespace Tests\Feature\Playtest\Utility;

use Tests\Feature\Playtest\BotProfile;
use Tests\Feature\Playtest\BotSession;

/**
 * The utility engine as ONE rule for PlaysSolLoop (spec 3.3). After a failed
 * decision the cursor skips every band below the failed one until the next
 * success or Sol — the same "continue with the next rule" semantics the legacy
 * loop has, so the adapter makes the same `when` calls.
 */
final class Collector
{
    public const MODES = ['adapter', 'utility'];

    private int $sol = -1;

    private int $cursorBand = -1;

    /** @param list<CandidateSource> $sources */
    public function __construct(
        private readonly array $sources,
        private readonly string $mode,
        private readonly BotProfile $profile,
        private readonly ?\Closure $apProbe = null,
    ) {
        if (! in_array($mode, self::MODES, true)) {
            throw new \InvalidArgumentException("Unknown collector mode: {$mode}");
        }
    }

    /** @return array{name:string, when:callable, do:callable} */
    public function rule(): array
    {
        return [
            'name' => 'utility',
            'when' => fn (BotSession $b) => $this->when($b),
            'do' => fn (BotSession $b, Decision $d) => $this->do($b, $d),
        ];
    }

    public function when(BotSession $b): ?Decision
    {
        if ($b->sol !== $this->sol) {
            $this->sol = $b->sol;
            $this->cursorBand = -1;
        }

        return UtilitySelector::selectFromSources($this->sources, $b, new BotContext(sol: $b->sol), $b->blockedThisSol, $this->cursorBand);
    }

    public function do(BotSession $b, Decision $d): array
    {
        $res = ($d->chosen->do)($b);
        $b->decisions[] = DecisionLog::entry($b->sol, $d, (bool) $res['ok']);
        $this->cursorBand = $res['ok'] ? -1 : $d->chosen->band;
        $res['block'] = $d->chosen->name;

        return $res;
    }
}
