<?php

namespace App\Http\Controllers\Colony;

use App\Http\Controllers\BaseController;
use App\Models\Run;
use App\Services\AdvisorService;
use App\Services\BarOfferDialogPresenter;
use App\Services\BarService;
use App\Services\ColonyService;
use App\Services\EventService;
use App\Services\MerchantService;
use App\Services\OnboardingHintService;
use App\Services\ResourcesService;
use App\Services\TickService;
use App\Services\TradeAdvantagePresenter;
use App\Services\TradeAdvantageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BarController extends BaseController
{
    private const BAR_BUILDING_ID = 52;

    public function __construct(
        TickService $tick,
        private readonly ColonyService $colonyService,
        private readonly BarService $barService,
        private readonly MerchantService $merchantService,
        private readonly EventService $eventService,
        private readonly OnboardingHintService $onboardingHintService,
        private readonly AdvisorService $advisorService,
        private readonly ResourcesService $resourcesService,
        private readonly BarOfferDialogPresenter $offerDialogPresenter,
        private readonly TradeAdvantagePresenter $advantagePresenter,
    ) {
        parent::__construct($tick);
    }

    public function index(): View
    {
        $colony = $this->colonyService->getPrimeColony(Auth::id());
        $tick = $this->tick->getTickCount();
        $currentSol = $this->currentSol();

        $barLevel = (int) DB::table('colony_buildings')
            ->where('colony_id', $colony->id)
            ->where('building_id', self::BAR_BUILDING_ID)
            ->value('level');

        $offers = $barLevel > 0
            ? $this->barService->getActiveOffers($colony->id, $tick)
            : collect();

        // Terms each offer actually executes at (Handelsvorteil applied) — the same
        // BarService::effectiveTerms() acceptOffer() books, so shown == executed.
        $offerTerms = [];
        $offerDialogs = [];
        $negotiateChance = $this->barService->negotiateChance($colony->id);
        foreach ($offers as $offer) {
            $offerTerms[$offer->id] = $this->barService->effectiveTerms($offer, $colony->id);
            // Player-facing breakdown (A13 P2b): sources, both negotiation outcomes, chance.
            $offerDialogs[$offer->id] = $this->offerDialogPresenter->present(
                $offerTerms[$offer->id],
                $negotiateChance,
                $colony->id,
            );
        }
        // Offers already negotiated on an earlier visit of the page start flagged, so the
        // dialog shows "Verhandelt: …" with a 0-AP Annehmen instead of offering Verhandeln again.
        $negotiatedOffers = array_map(fn () => true, array_filter($offerTerms, fn ($t) => $t['negotiated']));

        // Cantina header line + Corvan's price line (Handelsvorteil per channel).
        $tradeAdvantage = $barLevel > 0
            ? $this->advantagePresenter->forChannel($colony->id, TradeAdvantageService::CHANNEL_BAR)
            : null;

        $encounter = $barLevel > 0
            ? $this->barService->getActiveEncounter($colony->id, $tick)
            : null;

        $storyEncounterSlug = $barLevel > 0
            ? $this->barService->pickStoryEncounter($colony->id, $tick)
            : null;

        // Charakter-Anliegen (A41) — second, independent Cantina special-event slot.
        $concern = $barLevel > 0
            ? $this->barService->getActiveConcern($colony->id, $tick)
            : null;

        // Deva & Lenn Vier-Ausgänge-Pool (A42) — third, independent channel, not
        // gated by bar level's shared "one special event per tick" rule.
        $informationEncounter = $barLevel > 0
            ? $this->barService->getActiveInformationEncounter($colony->id, $tick)
            : null;

        $merchantVisit = $this->merchantService->getActiveVisit($colony->id, $tick);
        $merchantItems = $merchantVisit
            ? $this->merchantService->getPricedItemsForVisit($merchantVisit->id, $colony->id)
            : [];
        $merchantAdvantage = $merchantVisit
            ? $this->advantagePresenter->forChannel($colony->id, TradeAdvantageService::CHANNEL_MERCHANT)
            : null;

        // Marktbericht (Konsul, A13): read-only announcement of Corvan's next visit.
        $merchantForecast = $barLevel > 0
            ? $this->merchantService->getForecast($colony->id, $tick)
            : null;

        $hotspotsFile = base_path('data/cantina_hotspots.json');
        $hotspots = file_exists($hotspotsFile)
            ? (json_decode(file_get_contents($hotspotsFile), true) ?: [])
            : [];

        $run = Run::where('colony_id', $colony->id)->active()->first();
        $seed = $run ? $run->id : $colony->id;
        $characters = config('characters');

        $characterAssignment = [];
        foreach ($hotspots as $spotKey => $spot) {
            if (empty($spot['characters'])) {
                continue;
            }
            $idx = abs(crc32($seed.$spotKey)) % count($spot['characters']);
            $slug = $spot['characters'][$idx];
            $char = $characters[$slug] ?? null;
            if ($char) {
                $characterAssignment[$spotKey] = ['slug' => $slug] + $char;
            }
        }

        $firstVisit = $this->onboardingHintService->checkFirstVisit('cantina', Auth::id());
        $offerApCost = (int) config('game.bar.ap_cost_accept', 1);
        $negotiateApCost = (int) config('game.bar.ap_cost_negotiate', 2);
        $hasConsul = $barLevel > 0 && $this->barService->hasAvailableConsul($colony->id);

        // Knowledge dropdown options shared by Tomas (A40), Sarka's Anliegen (A41)
        // and Deva's knowledge_boost outcome (A42) — all 7 Kenntnisse are valid
        // targets for Tomas/Sarka; Deva's choice is further restricted client-side
        // to game.bar.information_pool.veteran.knowledge_choices.
        $knowledgeOptions = [];
        foreach (config('knowledge', []) as $key => $def) {
            $knowledgeOptions[] = ['id' => $def['id'], 'key' => $key, 'name' => __("knowledge.{$key}")];
        }

        $concernApCost = $concern ? (int) config("game.bar.concern.ap_cost.{$concern->character_slug}", 0) : null;

        $devaKnowledgeChoiceKeys = config('game.bar.information_pool.veteran.knowledge_choices', []);
        $devaKnowledgeChoices = array_values(array_filter(
            $knowledgeOptions,
            fn ($opt) => in_array($opt['key'], $devaKnowledgeChoiceKeys, true),
        ));

        return view('colony.bar', compact(
            'colony', 'offers', 'offerTerms', 'offerDialogs', 'negotiatedOffers', 'tradeAdvantage', 'merchantAdvantage', 'barLevel', 'currentSol',
            'merchantVisit', 'merchantItems', 'merchantForecast', 'hotspots', 'characterAssignment',
            'firstVisit', 'offerApCost', 'negotiateApCost', 'hasConsul',
            'encounter', 'storyEncounterSlug', 'concern', 'informationEncounter',
            'knowledgeOptions', 'concernApCost', 'devaKnowledgeChoices',
        ));
    }

    public function accept(Request $request, int $offerId): JsonResponse
    {
        $validated = $request->validate([
            'character_slug' => ['nullable', 'string', 'max:64'],
        ]);

        $userId = Auth::id();
        $colony = $this->colonyService->getPrimeColony($userId);
        $tick = $this->tick->getTickCount();
        $result = $this->barService->acceptOffer(
            $colony->id,
            $offerId,
            $userId,
            $tick,
            $validated['character_slug'] ?? null,
        );

        if ($result['ok']) {
            $this->eventService->createEvent([
                'user' => $userId,
                'tick' => $tick,
                'event' => 'trade.bar_accepted',
                'area' => 'trade',
                'parameters' => json_encode([
                    'colony_id' => $colony->id,
                    'give_resource_id' => $result['give_resource_id'],
                    'give_amount' => $result['give_amount'],
                    'get_resource_id' => $result['get_resource_id'],
                    'get_amount' => $result['get_amount'],
                ]),
            ]);
        }

        $result = $this->withResourcebarSync($result, $colony->id);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function negotiate(Request $request, int $offerId): JsonResponse
    {
        $userId = Auth::id();
        $colony = $this->colonyService->getPrimeColony($userId);
        $tick = $this->tick->getTickCount();
        $result = $this->barService->negotiateOffer($colony->id, $offerId, $userId, $tick);

        if ($result['ok'] && $result['success']) {
            $this->eventService->createEvent([
                'user' => $userId,
                'tick' => $tick,
                'event' => 'trade.bar_negotiated',
                'area' => 'trade',
                'parameters' => json_encode([
                    'colony_id' => $colony->id,
                    'give_resource_id' => $result['give_resource_id'],
                    'give_amount' => $result['give_amount'],
                    'get_resource_id' => $result['get_resource_id'],
                    'get_amount' => $result['get_amount'],
                ]),
            ]);
        }

        $result = $this->withResourcebarSync($result, $colony->id);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function acceptEncounter(Request $request, int $encounterId): JsonResponse
    {
        $userId = Auth::id();
        $colony = $this->colonyService->getPrimeColony($userId);
        $tick = $this->tick->getTickCount();
        $result = $this->barService->acceptEncounter($colony->id, $encounterId, $userId, $tick);

        if ($result['ok']) {
            $this->eventService->createEvent([
                'user' => $userId,
                'tick' => $tick,
                'event' => 'trade.bar_encounter_'.$result['type'],
                'area' => 'trade',
                'parameters' => json_encode([
                    'colony_id' => $colony->id,
                    'encounter_id' => $encounterId,
                ] + $result),
            ]);
        }

        if ($result['ok']) {
            $result['ap_available'] = $this->advisorService->getAvailableActionPoints($colony->id);
            $possessions = $this->resourcesService->getPossessionsByColonyId($colony->id);
            $result['credits_balance'] = $possessions[1]['amount'] ?? null;
            if (! empty($result['give_resource_id'])) {
                $result['give_resource_amount'] = $possessions[$result['give_resource_id']]['amount'] ?? null;
            }
        }

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * "Mit Tomas reden" (A40). No AP cost, no shared-pool interaction — Tomas
     * injects his bonus (if any) directly into the chosen knowledge, so there
     * is nothing here for the resourcebar to sync.
     */
    public function talkToBartender(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'knowledge_id' => ['required', 'integer'],
        ]);

        $userId = Auth::id();
        $colony = $this->colonyService->getPrimeColony($userId);
        $tick = $this->tick->getTickCount();

        $result = $this->barService->talkToBartender($colony->id, $validated['knowledge_id'], $tick);

        return response()->json(
            ['ok' => $result['success']] + $result,
            $result['success'] ? 200 : 422,
        );
    }

    /**
     * Resolves a Charakter-Anliegen (A41). AP for the concern's own cost
     * comes out of the shared colony pool, so the resourcebar's AP chip
     * (and, depending on the character's reward, Credits/Regolith/
     * Werkstoffe) must be able to sync live — see withResourcebarSync()'s
     * docblock for the general convention this follows.
     */
    public function resolveConcern(Request $request, int $concernId): JsonResponse
    {
        $validated = $request->validate([
            'knowledge_id' => ['nullable', 'integer'],
        ]);

        $userId = Auth::id();
        $colony = $this->colonyService->getPrimeColony($userId);
        $tick = $this->tick->getTickCount();

        $result = $this->barService->resolveConcern(
            $colony->id,
            $concernId,
            $userId,
            $tick,
            $validated['knowledge_id'] ?? null,
        );

        if ($result['ok']) {
            $result['ap_available'] = $this->advisorService->getAvailableActionPoints($colony->id);
            $possessions = $this->resourcesService->getPossessionsByColonyId($colony->id);
            $result['credits_balance'] = $possessions[1]['amount'] ?? null;
            $result['regolith_balance'] = $possessions[3]['amount'] ?? null;
            $result['compounds_balance'] = $possessions[4]['amount'] ?? null;
        }

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Resolves a Deva & Lenn Vier-Ausgänge-Pool encounter (A42). No AP cost
     * from the shared pool (see BarService::resolveInformationEncounter()
     * docblock) and no resource change either — nothing here needs a
     * resourcebar sync.
     */
    public function resolveInformationEncounter(Request $request, int $encounterId): JsonResponse
    {
        $validated = $request->validate([
            'knowledge_id' => ['nullable', 'integer'],
        ]);

        $userId = Auth::id();
        $colony = $this->colonyService->getPrimeColony($userId);
        $tick = $this->tick->getTickCount();

        $result = $this->barService->resolveInformationEncounter(
            $colony->id,
            $encounterId,
            $userId,
            $tick,
            $validated['knowledge_id'] ?? null,
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /**
     * Adds the fresh totals the resourcebar needs to sync live after an
     * AP-/resource-changing AJAX action (project convention — every such action
     * must be able to update the resourcebar without a full page reload).
     * ap_available is added whenever the request succeeded at all (accept always
     * spends AP on success; negotiate spends it on both a win and a loss), the
     * give/get resource balances only when a trade actually happened.
     */
    private function withResourcebarSync(array $result, int $colonyId): array
    {
        if (! $result['ok']) {
            return $result;
        }

        $result['ap_available'] = $this->advisorService->getAvailableActionPoints($colonyId);

        if (isset($result['give_resource_id'], $result['get_resource_id'])) {
            $possessions = $this->resourcesService->getPossessionsByColonyId($colonyId);
            $result['give_resource_amount'] = $possessions[$result['give_resource_id']]['amount'] ?? null;
            $result['get_resource_amount'] = $possessions[$result['get_resource_id']]['amount'] ?? null;
        }

        return $result;
    }
}
