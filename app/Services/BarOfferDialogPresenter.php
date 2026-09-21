<?php

namespace App\Services;

/**
 * The numbers and texts of the Cantina offer dialog (GDD §12 "Was der Angebotsdialog
 * zeigt", A13/P2b). Pure presentation on top of BarService::effectiveTerms() and
 * negotiateChance(): nothing is re-derived here, so what the dialog shows is what
 * acceptOffer() books and what negotiateOffer() rolls.
 *
 * Amount ladder (Get side), all rounded by the service:
 *   base_get -> amount_safe (Handelsvorteil) -> amount_negotiated (+ Verhandlungs-Aufschlag)
 * The shown "plus" values and the negotiation share are differences of these
 * rounded totals, so the parts of the sum line always add up exactly.
 */
class BarOfferDialogPresenter
{
    private const RES_CREDITS = 1;

    public function __construct(
        private readonly TradeAdvantageService $tradeAdvantageService,
        private readonly TradeAdvantagePresenter $advantagePresenter,
    ) {}

    /**
     * @param  array  $terms  BarService::effectiveTerms() result.
     * @param  array  $chance  BarService::negotiateChance() result.
     * @return array<string, mixed>
     */
    public function present(array $terms, array $chance, int $colonyId): array
    {
        $fixed = $terms['fixed_price'];
        $negotiated = $terms['negotiated'];
        $baseGet = $terms['base_get_amount'];
        $giveId = $terms['give_resource_id'];
        $getId = $terms['get_resource_id'];
        $giveLabel = $this->resourceLabel($giveId);
        $getLabel = $this->resourceLabel($getId);

        // Advantage-only amount: get_amount itself already includes the bonus once negotiated.
        $amountSafe = $negotiated
            ? $this->tradeAdvantageService->applyToAmount($baseGet, $terms['advantage'])
            : $terms['get_amount'];
        $amountNegotiated = $negotiated ? $terms['get_amount'] : $terms['get_amount_if_negotiated'];
        $canNegotiate = ! $fixed && ! $negotiated && $chance['rank'] > 0;
        $plusSafe = $amountSafe - $baseGet;
        $bonusAmount = $amountNegotiated !== null ? $amountNegotiated - $amountSafe : null;
        $negotiationPercent = ($fixed || $chance['rank'] === 0 && ! $negotiated)
            ? null
            : (int) round($terms['negotiation_bonus_offered'] * 100);

        $advantage = $this->advantagePresenter->present($colonyId, $terms['advantage']);

        return [
            'fixed_price' => $fixed,
            'negotiated' => $negotiated,
            'can_negotiate' => $canNegotiate,
            'credits_give' => $giveId === self::RES_CREDITS,
            'give_resource_id' => $giveId,
            'get_resource_id' => $getId,
            'give_label' => $giveLabel,
            'get_label' => $getLabel,
            'give_amount' => $terms['give_amount'],
            'base_give' => $terms['base_give_amount'],
            'base_get' => $baseGet,
            'advantage' => $advantage,
            'amount_safe' => $amountSafe,
            'plus_safe' => $plusSafe,
            'amount_negotiated' => $amountNegotiated,
            'plus_negotiated' => $amountNegotiated !== null ? $amountNegotiated - $baseGet : null,
            'bonus_amount' => $bonusAmount,
            'negotiation_percent' => $negotiationPercent,
            'chance' => $canNegotiate ? $this->chance($chance) : null,
            'negotiation_sum' => $amountNegotiated !== null && $negotiationPercent !== null
                ? __('colony.bar_offer_negotiation_sum', [
                    'percent' => $negotiationPercent,
                    'base' => $baseGet,
                    'advantage' => $plusSafe,
                    'bonus' => $bonusAmount,
                    'total' => $amountNegotiated,
                    'resource' => $getLabel,
                ])
                : null,
            'base_credits_text' => $giveId === self::RES_CREDITS
                ? __('colony.bar_offer_base_credits', [
                    'amount' => $baseGet,
                    'resource' => $getLabel,
                    'price' => $terms['give_amount'],
                ])
                : null,
            'price_stays_text' => $giveId === self::RES_CREDITS
                ? __('colony.bar_offer_price_stays', ['price' => $terms['give_amount']])
                : null,
            'fixed_price_note' => $fixed
                ? __('colony.bar_offer_fixed_price_note', [
                    'amount' => $terms['give_amount'],
                    'resource' => $giveLabel,
                    'credits' => $terms['get_amount'],
                ])
                : null,
        ];
    }

    /**
     * @return array{total_percent: int, lose_percent: int, breakdown: string}
     */
    private function chance(array $chance): array
    {
        // A capped total no longer equals base + knowledge — the guard rail is never
        // explained (GDD §12), so the breakdown then shows the total only.
        if ($chance['capped']) {
            $breakdown = __('colony.bar_offer_chance_total_only', ['total' => $chance['total_percent']]);
        } elseif ($chance['knowledge_bonus_percent'] > 0) {
            $breakdown = __('colony.bar_offer_chance_breakdown', [
                'total' => $chance['total_percent'],
                'rank' => $chance['rank'],
                'base' => $chance['base_percent'],
                'bonus' => $chance['knowledge_bonus_percent'],
            ]);
        } else {
            $breakdown = __('colony.bar_offer_chance_base_only', [
                'total' => $chance['total_percent'],
                'rank' => $chance['rank'],
            ]);
        }

        return [
            'total_percent' => $chance['total_percent'],
            'lose_percent' => 100 - $chance['total_percent'],
            'breakdown' => $breakdown,
        ];
    }

    private function resourceLabel(int $resourceId): string
    {
        return match ($resourceId) {
            1 => __('resources.res_credits'),
            3 => __('resources.res_regolith'),
            4 => __('resources.res_werkstoffe'),
            5 => __('resources.res_organika'),
            default => '?',
        };
    }
}
