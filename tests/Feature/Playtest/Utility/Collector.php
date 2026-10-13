<?php

namespace Tests\Feature\Playtest\Utility;

use App\Services\AdvisorService;
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
    public const MODES = ['adapter', 'utility', 'shadow'];

    private int $sol = -1;

    private int $cursorBand = -1;

    private array $weights = [];

    private ?float $pendingMarginal = null;

    private ?float $lastMarginal = null;

    private bool $endedIdle = false;

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
        $this->weights = $profile->weights();
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
            if ($this->mode !== 'adapter' && $this->sol >= 0) {
                $this->closeSol($b);
            }
            $this->sol = $b->sol;
            $this->cursorBand = -1;
            $this->lastMarginal = null;
            $this->endedIdle = false;
        }

        $shadow = $this->mode === 'shadow';
        $ctx = $shadow ? BotContext::capture($b, $this->profile) : new BotContext(sol: $b->sol);
        $minNet = (float) $this->weights['min_net'];
        $decision = UtilitySelector::selectFromSources($this->sources, $b, $ctx, $b->blockedThisSol, $this->cursorBand, $minNet, fullSearch: $shadow);

        if ($decision === null) {
            if ($this->mode !== 'adapter') {
                $this->endedIdle = $this->availableAp($b) > 0;
            }

            return null;
        }
        $this->pendingMarginal = $decision->chosen->isScored() ? $decision->chosen->milliScore() / 1000 : null;

        return $shadow ? $this->withShadow($decision, $b, $ctx) : $decision;
    }

    /** Scores every collected legacy candidate it can and records what a utility engine would pick. */
    private function withShadow(Decision $decision, BotSession $b, BotContext $ctx): Decision
    {
        $scored = array_map(function (Candidate $c) use ($b, $ctx): Candidate {
            if ($c->isScored() || $c->mandatory) {
                return $c;
            }
            $estimate = LegacyValuation::estimate($c, $b, $ctx);

            return $estimate === null ? $c : $c->withEstimate($estimate);
        }, $decision->all);

        $chosenEstimate = null;
        foreach ($scored as $c) {
            if ($c->key === $decision->chosen->key && $c->band === $decision->chosen->band && $c->isScored()) {
                $this->pendingMarginal = $c->milliScore() / 1000;
                $chosenEstimate = $c;
            }
        }
        $minNet = (float) $this->weights['min_net'];
        $blocked = $b->blockedThisSol;

        return $decision->withShadow(
            UtilitySelector::shadowPick($scored, $blocked, $minNet, $this->cursorBand),
            UtilitySelector::freePick($scored, $blocked, $minNet, $this->cursorBand),
            UtilitySelector::shadowTop3($scored, $blocked, $minNet, $this->cursorBand),
            $chosenEstimate,
        );
    }

    /** Spec 2.4: λ_AP ← EMA(marginal density of the closed Sol); idle AP at the end count as 0. */
    private function closeSol(BotSession $b): void
    {
        $marginal = $this->endedIdle ? 0.0 : $this->lastMarginal;
        if ($marginal === null) {
            return;
        }
        $previous = $b->lambdaAp ?? (float) $this->weights['lambda_ap_start'];
        $b->lambdaAp = Valuation::lambdaEma($previous, $marginal, (float) $this->weights['lambda_ap_ema']);
        $b->lambdaBySol[$this->sol] = round($b->lambdaAp, 4);
    }

    private function availableAp(BotSession $b): int
    {
        return $this->apProbe !== null
            ? ($this->apProbe)($b)
            : app(AdvisorService::class)->getAvailableActionPoints($b->colonyId);
    }

    public function do(BotSession $b, Decision $d): array
    {
        $res = ($d->chosen->do)($b);
        $b->decisions[] = DecisionLog::entry($b->sol, $d, (bool) $res['ok']);
        $this->cursorBand = $res['ok'] ? -1 : $d->chosen->band;
        if ($res['ok'] && $this->pendingMarginal !== null) {
            $this->lastMarginal = $this->pendingMarginal;
        }
        $this->endedIdle = false;
        $res['block'] = $d->chosen->name;

        return $res;
    }
}
