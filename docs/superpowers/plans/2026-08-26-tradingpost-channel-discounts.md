# Handelsposten-Kanal-Rabatte Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Handelsposten (tradingPost) bekommt seine erste echte Spielwirkung — je Ausbaustufe schaltet sich ein Rabatt-Kanal auf einen der drei bestehenden Handelswege frei (Cantina-Zufallsangebote → Reisender Händler → Nexus/Corporate Contact), statt der bisher komplett wirkungslosen `merchant_price_bonus`-Config.

**Architecture:** Ein neuer, kleiner `TradingPostService` kapselt die Level-Schwellen-Logik (`discountFor(int $colonyId, string $channel): float`) und liest den Rabattsatz aus dem bereits bestehenden `config('buildings.tradingPost.merchant_price_bonus')`. Die drei bestehenden Handels-Services (`BarService`, `MerchantService`, `CorporateContactService`) rufen diesen Service beim tatsächlichen Preis-/Mengen-Vollzug auf — nicht bei der Angebots-Generierung, damit Level-Änderungen sofort wirken, ohne bereits generierte Angebote invalidieren zu müssen. `BarService` wendet den Rabatt nur an, wenn das Angebot NICHT bereits über den Konsul verhandelt wurde (GDD-Vorgabe: kein Stack-Effekt zwischen Konsul-Rang-Verhandlungsbonus und Handelsposten-Rabatt).

**Tech Stack:** PHP/Laravel, PHPUnit (`bin/phpunit`), Larastan (`bin/phpstan`).

**Spec:** `docs/superpowers/specs/2026-08-23-building-tier-system-design.md` (Abschnitt „Handelsposten (tradingPost) — alle 3 Stufen neu")

## Global Constraints

- Kanal-Schwellen (aus der Spec, bindend): Stufe 1 = Rabatt auf Cantina-Zufallsangebote, Stufe 2 = zusätzlich Reisender Händler, Stufe 3 = zusätzlich Nexus/Corporate Contact. Jede höhere Stufe schaltet zusätzlich zu den niedrigeren frei (kumulativ, nicht exklusiv).
- Rabattsatz kommt unverändert aus dem bereits vorhandenen `config('buildings.tradingPost.merchant_price_bonus')` (aktuell 0.12) — keine neue Zahl einführen, keine Kalibrierung in diesem Plan (ADR 0004, Zahlen-Feinschliff ist Folge-Task nach Playtest).
- Kein Stack-Effekt mit dem Konsul-Rang-Verhandlungsbonus (`BarService::negotiateOffer()`) — der Handelsposten-Rabatt gilt nur für nicht-verhandelte Cantina-Angebote (`$offer->is_negotiated === false`).
- TDD-Pflicht für alle Tasks außer Task 1 (reine Enum-Ergänzung ohne eigenes Verhalten) und Task 6 (Doku).
- Reihenfolge der Tasks ist absichtlich: Task 2 (Service) muss vor 3-5 (Verdrahtung) stehen, da 3-5 den Service konsumieren.

---

### Task 1: `BuildingId::TradingPost` Enum-Case ergänzen

**Files:**
- Modify: `app/Enums/BuildingId.php`

**Interfaces:**
- Produces: `BuildingId::TradingPost` (int-backed, Wert `55`) — konsumiert von Task 2's `TradingPostService`.

- [ ] **Step 1: Case ergänzen**

In `app/Enums/BuildingId.php`, nach `case UplinkStation = 54;` einfügen:

```php
    case TradingPost = 55;
```

- [ ] **Step 2: Syntax prüfen**

Run: `php -l app/Enums/BuildingId.php`
Expected: `No syntax errors detected`

- [ ] **Step 3: Commit**

```bash
git add app/Enums/BuildingId.php
git commit -m "feat: BuildingId::TradingPost Enum-Case ergänzen"
```

---

### Task 2: `TradingPostService::discountFor()` (TDD)

**Files:**
- Create: `app/Services/TradingPostService.php`
- Test: `tests/Feature/TradingPostServiceTest.php`

**Interfaces:**
- Consumes: `BuildingId::TradingPost` (Task 1), `config('buildings.tradingPost.merchant_price_bonus')`.
- Produces: `TradingPostService::discountFor(int $colonyId, string $channel): float` — konsumiert von Task 3 (`'bar'`), Task 4 (`'merchant'`), Task 5 (`'corporate_contact'`). Gültige `$channel`-Werte: `'bar'`, `'merchant'`, `'corporate_contact'`. Rückgabe: der konfigurierte Rabattsatz (float, z.B. `0.12`), oder `0.0` wenn kein Handelsposten gebaut ist oder dessen Level die Kanal-Schwelle nicht erreicht. Ein unbekannter `$channel`-String liefert `0.0` (kein Fehler).

- [ ] **Step 1: Fehlschlagenden Test schreiben**

Neue Datei `tests/Feature/TradingPostServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Services\TradingPostService;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TradingPostService::discountFor() — Kanal-Rabatt-Schwellen (Design-Spec
 * 2026-08-23, Abschnitt "Handelsposten"): Stufe 1 = Cantina, Stufe 2 = +Reisender
 * Händler, Stufe 3 = +Nexus/Corporate Contact. Kumulativ, nicht exklusiv.
 */
class TradingPostServiceTest extends TestCase
{
    use RefreshDatabase;

    private const COLONY_ID = 1;

    private const TRADING_POST_ID = 55;

    private TradingPostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(TestSeeder::class)->run();
        $this->service = $this->app->make(TradingPostService::class);
    }

    private function setTradingPostLevel(?int $level): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', self::TRADING_POST_ID)->delete();

        if ($level !== null) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID,
                'building_id' => self::TRADING_POST_ID,
                'instance_id' => 1,
                'level' => $level,
                'status_points' => 20,
                'ap_spend' => 0,
            ]);
        }
    }

    public function test_no_trading_post_gives_zero_discount_on_every_channel(): void
    {
        $this->setTradingPostLevel(null);

        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'bar'));
        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'merchant'));
        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'corporate_contact'));
    }

    public function test_level_1_unlocks_only_bar_channel(): void
    {
        $this->setTradingPostLevel(1);

        $expected = (float) config('buildings.tradingPost.merchant_price_bonus');
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'bar'));
        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'merchant'));
        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'corporate_contact'));
    }

    public function test_level_2_unlocks_bar_and_merchant_cumulatively(): void
    {
        $this->setTradingPostLevel(2);

        $expected = (float) config('buildings.tradingPost.merchant_price_bonus');
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'bar'));
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'merchant'));
        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'corporate_contact'));
    }

    public function test_level_3_unlocks_all_three_channels(): void
    {
        $this->setTradingPostLevel(3);

        $expected = (float) config('buildings.tradingPost.merchant_price_bonus');
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'bar'));
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'merchant'));
        $this->assertSame($expected, $this->service->discountFor(self::COLONY_ID, 'corporate_contact'));
    }

    public function test_unknown_channel_returns_zero_not_an_error(): void
    {
        $this->setTradingPostLevel(3);

        $this->assertSame(0.0, $this->service->discountFor(self::COLONY_ID, 'not_a_real_channel'));
    }
}
```

- [ ] **Step 2: Test laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit --filter TradingPostServiceTest`
Expected: FAIL — Klasse `App\Services\TradingPostService` existiert nicht.

- [ ] **Step 3: `TradingPostService` implementieren**

Neue Datei `app/Services/TradingPostService.php`:

```php
<?php

namespace App\Services;

use App\Enums\BuildingId;
use Illuminate\Support\Facades\DB;

/**
 * Handelsposten (tradingPost) — Kanal-Rabatt-Freischaltung (Design-Spec
 * 2026-08-23, Abschnitt "Handelsposten"). Jede Ausbaustufe schaltet einen
 * zusätzlichen Handelskanal für den (bisher toten) merchant_price_bonus frei:
 * Stufe 1 = Cantina-Zufallsangebote, Stufe 2 = + Reisender Händler,
 * Stufe 3 = + Nexus/Corporate Contact. Kumulativ, nicht exklusiv — Stufe 3
 * gewährt den Rabatt auf allen drei Kanälen gleichzeitig.
 */
class TradingPostService
{
    /** @var array<string, int> Handelskanal => benötigte Handelsposten-Stufe */
    private const CHANNEL_THRESHOLDS = [
        'bar' => 1,
        'merchant' => 2,
        'corporate_contact' => 3,
    ];

    public function discountFor(int $colonyId, string $channel): float
    {
        $threshold = self::CHANNEL_THRESHOLDS[$channel] ?? null;
        if ($threshold === null) {
            return 0.0;
        }

        $level = (int) (DB::table('colony_buildings')
            ->where('colony_id', $colonyId)
            ->where('building_id', BuildingId::TradingPost->value)
            ->value('level') ?? 0);

        if ($level < $threshold) {
            return 0.0;
        }

        return (float) config('buildings.tradingPost.merchant_price_bonus', 0.0);
    }
}
```

- [ ] **Step 4: Test laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit --filter TradingPostServiceTest`
Expected: PASS (6 Tests)

- [ ] **Step 5: Larastan prüfen**

Run: `bin/phpstan analyse app/Services/TradingPostService.php --no-progress`
Expected: `[OK] No errors`

- [ ] **Step 6: Commit**

```bash
git add app/Services/TradingPostService.php tests/Feature/TradingPostServiceTest.php
git commit -m "feat: TradingPostService mit Kanal-Rabatt-Schwellen (Cantina/Händler/Nexus)"
```

---

### Task 3: Rabatt in `BarService::acceptOffer()` verdrahten (TDD)

**Files:**
- Modify: `app/Services/BarService.php`
- Test: `tests/Feature/Bar/BarServiceTest.php`

**Interfaces:**
- Consumes: `TradingPostService::discountFor($colonyId, 'bar')` (Task 2).
- Produces: nichts Neues nach außen — `acceptOffer()`s Rückgabeformat bleibt gleich, nur `give_amount`/`get_amount` sind bei aktivem Kanal-Rabatt günstiger für den Spieler.

- [ ] **Step 1: Fehlschlagende Tests schreiben**

In `tests/Feature/Bar/BarServiceTest.php`, am Ende der Klasse (vor der schließenden `}`) einfügen — orientiert an der bestehenden `test_accept_offer_with_credits_as_give()`-Struktur:

```php
    // ── Handelsposten-Rabatt (Design-Spec 2026-08-23) ──────────────────────────

    private function setTradingPostLevel(?int $level): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 55)->delete();

        if ($level !== null) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID,
                'building_id' => 55,
                'instance_id' => 1,
                'level' => $level,
                'status_points' => 20,
                'ap_spend' => 0,
            ]);
        }
    }

    public function test_accept_offer_applies_trading_post_discount_when_credits_are_the_give_side(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->setTradingPostLevel(1); // Stufe 1 schaltet den Cantina-Kanal frei

        $giveAmount = 500;
        $getAmount = 20;

        $this->setCredits(1000);
        $this->setColonyResource(self::RES_REGOLITH, 0);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_CREDITS,
            'give_amount' => $giveAmount,
            'get_resource_id' => self::RES_REGOLITH,
            'get_amount' => $getAmount,
            'expires_tick' => 20,
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertTrue($result['ok']);
        $bonus = (float) config('buildings.tradingPost.merchant_price_bonus');
        $expectedGive = (int) max(1, round($giveAmount * (1 - $bonus)));
        $this->assertSame($expectedGive, $result['give_amount'], 'give_amount (Credits) must be discounted by the trading post channel rate');
        $this->assertSame(1000 - $expectedGive, $this->getCredits());
    }

    public function test_accept_offer_has_no_discount_without_trading_post(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->setTradingPostLevel(null);

        $giveAmount = 500;
        $this->setCredits(1000);
        $this->setColonyResource(self::RES_REGOLITH, 0);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_CREDITS,
            'give_amount' => $giveAmount,
            'get_resource_id' => self::RES_REGOLITH,
            'get_amount' => 20,
            'expires_tick' => 20,
        ]);

        $result = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertSame($giveAmount, $result['give_amount'], 'no trading post → no discount, unchanged amount');
    }

    public function test_accept_offer_does_not_stack_trading_post_discount_with_negotiation(): void
    {
        $this->clearBarOffers();
        $this->mockTick(10);
        $this->setTradingPostLevel(1);
        $this->assignTrader(3); // rank 3 Konsul, siehe bestehende assignTrader()-Helper

        $giveAmount = 500;
        $this->setCredits(1000);
        $this->setColonyResource(self::RES_REGOLITH, 0);

        $offerId = $this->insertOffer([
            'give_resource_id' => self::RES_CREDITS,
            'give_amount' => $giveAmount,
            'get_resource_id' => self::RES_REGOLITH,
            'get_amount' => 20,
            'expires_tick' => 20,
        ]);

        // Verhandeln setzt is_negotiated=true und passt give_amount bereits über den
        // Konsul-Rang-Bonus an — der Handelsposten-Rabatt darf hier NICHT zusätzlich
        // draufkommen (GDD: kein Stack-Effekt).
        config(['game.bypass.ap_checks' => true]);
        $negotiateResult = $this->barService->negotiateOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);
        $this->assertTrue($negotiateResult['ok']);
        $this->assertTrue($negotiateResult['success'] ?? false, 'negotiate must succeed for this assertion to be meaningful — check negotiate_success_chance fixture for rank 3');

        $negotiatedGiveAmount = DB::table('bar_offers')->where('id', $offerId)->value('give_amount');

        $acceptResult = $this->barService->acceptOffer(self::COLONY_ID, $offerId, self::USER_ID, 10);

        $this->assertSame((int) $negotiatedGiveAmount, $acceptResult['give_amount'], 'trading post discount must not stack on top of an already-negotiated offer');
    }
```

**Hinweis für den Implementierer:** `test_accept_offer_does_not_stack_trading_post_discount_with_negotiation` hängt von `config('game.bar.negotiate_success_chance.3')` ab, das in den Testdaten >0 sein muss, damit der Roll zuverlässig erfolgreich ist. Falls der Test flaky ist (Zufalls-Roll schlägt manchmal fehl), prüfe `pseudoRand()` in `BarService` — der Roll ist deterministisch aus `$offer->id * 7919 + $currentTick * 131`, mit den festen Werten in diesem Test (offerId aus `insertOffer()`, tick=10) sollte das Ergebnis reproduzierbar sein. Falls der Roll bei diesen konkreten Werten fehlschlägt, passe `$currentTick` im Testaufruf an, bis der Roll erfolgreich ist — nicht die Erfolgswahrscheinlichkeit in der Config ändern.

- [ ] **Step 2: Tests laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit --filter test_accept_offer_applies_trading_post_discount_when_credits_are_the_give_side`
Expected: FAIL — `give_amount` ist noch nicht rabattiert (voller `$giveAmount` statt reduziertem Wert).

- [ ] **Step 3: Rabatt in `acceptOffer()` verdrahten**

In `app/Services/BarService.php`, im Konstruktor `TradingPostService` als Abhängigkeit ergänzen:

```php
    public function __construct(
        private readonly ResourcesService $resourcesService,
        private readonly AdvisorService $advisorService,
        private readonly TradingPostService $tradingPostService,
    ) {}
```

(Import ergänzen: `use App\Services\TradingPostService;` — falls die Datei bereits im selben Namespace `App\Services` liegt, ist kein `use` nötig, `BarService` liegt selbst in `App\Services`, kein Import erforderlich.)

In `acceptOffer()`, direkt vor dem `DB::transaction(function () use ($offer, $colonyId, $apCost): void {`-Block (unmittelbar nach dem Reserve-Floor-Check) einfügen:

```php
        // Handelsposten-Kanal-Rabatt (Design-Spec 2026-08-23) — nur auf
        // nicht-verhandelte Angebote, kein Stack-Effekt mit dem
        // Konsul-Rang-Verhandlungsbonus aus negotiateOffer().
        $giveAmount = $offer->give_amount;
        $getAmount = $offer->get_amount;
        if (! $offer->is_negotiated) {
            $discount = $this->tradingPostService->discountFor($colonyId, 'bar');
            if ($discount > 0.0) {
                $isCreditsOffer = $offer->give_resource_id === self::RES_CREDITS;
                $giveAmount = $isCreditsOffer
                    ? (int) max(1, round($offer->give_amount * (1 - $discount)))
                    : $offer->give_amount;
                $getAmount = $isCreditsOffer
                    ? $offer->get_amount
                    : (int) max(1, round($offer->get_amount * (1 + $discount)));
            }
        }
```

Dann im `DB::transaction(...)`-Block die Verwendung von `$offer->give_amount`/`$offer->get_amount` durch die neuen lokalen Variablen `$giveAmount`/`$getAmount` ersetzen:

```php
        DB::transaction(function () use ($offer, $colonyId, $apCost, $giveAmount, $getAmount): void {
            $this->resourcesService->decreaseAmount($colonyId, $offer->give_resource_id, $giveAmount);
            $this->resourcesService->increaseAmount($colonyId, $offer->get_resource_id, $getAmount);
            $offer->is_accepted = true;
            $offer->save();
            if ($apCost > 0) {
                $this->advisorService->lockActionPoints($colonyId, $apCost, self::TRADER_ADVISOR_ID);
            }
        });
```

Und im anschließenden `Log::info('bar_trade', [...])`-Aufruf sowie im finalen Return-Array `give_amount`/`get_amount` ebenfalls auf `$giveAmount`/`$getAmount` umstellen (statt `$offer->give_amount`/`$offer->get_amount`), damit Logging und Rückgabewert den tatsächlich vollzogenen (ggf. rabattierten) Betrag zeigen:

```php
        Log::info('bar_trade', [
            'colony_id' => $colonyId,
            'offer_id' => $offerId,
            'give_resource_id' => $offer->give_resource_id,
            'give_amount' => $giveAmount,
            'get_resource_id' => $offer->get_resource_id,
            'get_amount' => $getAmount,
        ]);

        return [
            'ok' => true,
            'give_resource_id' => $offer->give_resource_id,
            'give_amount' => $giveAmount,
            'get_resource_id' => $offer->get_resource_id,
            'get_amount' => $getAmount,
        ];
```

- [ ] **Step 4: Tests laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit --filter BarServiceTest`
Expected: PASS (alle bisherigen Tests weiterhin grün + die 3 neuen)

- [ ] **Step 5: Larastan prüfen**

Run: `bin/phpstan analyse app/Services/BarService.php --no-progress`
Expected: `[OK] No errors`

- [ ] **Step 6: Commit**

```bash
git add app/Services/BarService.php tests/Feature/Bar/BarServiceTest.php
git commit -m "feat: Handelsposten-Rabatt in BarService::acceptOffer() verdrahten (kein Stack mit Konsul-Verhandlung)"
```

---

### Task 4: Rabatt in `MerchantService::buyItem()` verdrahten (TDD)

**Files:**
- Modify: `app/Services/MerchantService.php`
- Test: `tests/Feature/MerchantServiceTest.php`

**Interfaces:**
- Consumes: `TradingPostService::discountFor($colonyId, 'merchant')` (Task 2).
- Produces: `buyItem()`s Rückgabe-Array bekommt das bestehende Format, `cost_credits` im geloggten/abgerechneten Betrag ist bei aktivem Kanal-Rabatt niedriger.

- [ ] **Step 1: Fehlschlagende Tests schreiben**

In `tests/Feature/MerchantServiceTest.php`, am Ende der Klasse (vor der schließenden `}`) einfügen — nutzt die bestehenden Helper `insertVisit()`/`insertItem()`/`setCredits()`/`getCredits()`/`mockTick()`:

```php
    // ── Handelsposten-Rabatt (Design-Spec 2026-08-23) ──────────────────────────

    private function setTradingPostLevel(?int $level): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 55)->delete();

        if ($level !== null) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID,
                'building_id' => 55,
                'instance_id' => 1,
                'level' => $level,
                'status_points' => 20,
                'ap_spend' => 0,
            ]);
        }
    }

    public function test_buy_item_applies_trading_post_discount_to_cost(): void
    {
        $this->mockTick(20);
        $this->setTradingPostLevel(2); // Stufe 2 schaltet den Reisender-Händler-Kanal frei

        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);
        $itemId = $this->insertItem($visitId, [
            'item_type' => 'trust_boost',
            'payload' => json_encode(['trust_amount' => 15]),
            'cost_credits' => 100,
        ]);
        $this->setCredits(300);
        $this->setColonyResource(self::TRUST_RESOURCE_ID, 50);

        $result = $this->service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $this->assertTrue($result['ok']);
        $discount = (float) config('buildings.tradingPost.merchant_price_bonus');
        $expectedCharge = (int) max(1, round(100 * (1 - $discount)));
        $this->assertSame(300 - $expectedCharge, $result['credits'], 'buyItem must deduct the trading-post-discounted cost, not the full cost_credits');
        $this->assertSame(300 - $expectedCharge, $this->getCredits());
    }

    public function test_buy_item_has_no_discount_without_trading_post(): void
    {
        $this->mockTick(20);
        $this->setTradingPostLevel(null);

        $visitId = $this->insertVisit(['tick_start' => 20, 'tick_end' => 21]);
        $itemId = $this->insertItem($visitId, [
            'item_type' => 'trust_boost',
            'payload' => json_encode(['trust_amount' => 15]),
            'cost_credits' => 100,
        ]);
        $this->setCredits(300);
        $this->setColonyResource(self::TRUST_RESOURCE_ID, 50);

        $result = $this->service->buyItem($itemId, self::COLONY_ID, self::USER_ID);

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['credits'], 'no trading post → full cost_credits (100) deducted, unchanged from pre-existing behaviour');
    }
```

- [ ] **Step 2: Tests laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit --filter test_buy_item_applies_trading_post_discount_to_cost`
Expected: FAIL — `$result['credits']` ist `200` (voller Abzug) statt des rabattierten Werts.

- [ ] **Step 3: Rabatt in `buyItem()` verdrahten**

In `app/Services/MerchantService.php`, Konstruktor um `TradingPostService` ergänzen (gleiches Muster wie Task 3 Step 3):

```php
    public function __construct(
        private readonly AdvisorService $advisorService,
        private readonly BarService $barService,
        private readonly ResourcesService $resourcesService,
        private readonly TradingPostService $tradingPostService,
    ) {}
```

In `buyItem(int $itemId, int $colonyId, int $userId): array`: der Rabatt muss VOR dem Credits-Check berechnet werden, damit ein Spieler, der sich den vollen Preis nicht leisten kann, den rabattierten aber schon, nicht fälschlich abgewiesen wird. Ersetze den bestehenden Block

```php
        // Check credits
        $credits = (int) (DB::table('user_resources')
            ->where('user_id', $userId)
            ->value('credits') ?? 0);

        if ($credits < $item->cost_credits) {
            return ['ok' => false, 'error' => 'Nicht genug Credits.'];
        }

        // Deduct credits, apply effect and mark sold atomically.
        DB::transaction(function () use ($item, $itemId, $colonyId, $userId): void {
            DB::table('user_resources')
                ->where('user_id', $userId)
                ->decrement('credits', $item->cost_credits);

            $this->applyItemEffect($item, $colonyId);

            DB::table('merchant_items')
                ->where('id', $itemId)
                ->update(['sold' => true, 'updated_at' => now()]);
        });

        Log::info('merchant_purchase', [
            'colony_id' => $colonyId,
            'user_id' => $userId,
            'item_id' => $itemId,
            'item_type' => $item->item_type,
            'cost_credits' => $item->cost_credits,
        ]);
```

durch:

```php
        // Handelsposten-Kanal-Rabatt (Design-Spec 2026-08-23) — Stufe 2 schaltet
        // den Reisender-Händler-Kanal frei. Vor dem Credits-Check berechnet, damit
        // die Affordability-Prüfung gegen den tatsächlich fälligen Betrag läuft.
        $discount = $this->tradingPostService->discountFor($colonyId, 'merchant');
        $chargedCredits = $discount > 0.0
            ? (int) max(1, round($item->cost_credits * (1 - $discount)))
            : $item->cost_credits;

        // Check credits
        $credits = (int) (DB::table('user_resources')
            ->where('user_id', $userId)
            ->value('credits') ?? 0);

        if ($credits < $chargedCredits) {
            return ['ok' => false, 'error' => 'Nicht genug Credits.'];
        }

        // Deduct credits, apply effect and mark sold atomically.
        DB::transaction(function () use ($item, $itemId, $colonyId, $userId, $chargedCredits): void {
            DB::table('user_resources')
                ->where('user_id', $userId)
                ->decrement('credits', $chargedCredits);

            $this->applyItemEffect($item, $colonyId);

            DB::table('merchant_items')
                ->where('id', $itemId)
                ->update(['sold' => true, 'updated_at' => now()]);
        });

        Log::info('merchant_purchase', [
            'colony_id' => $colonyId,
            'user_id' => $userId,
            'item_id' => $itemId,
            'item_type' => $item->item_type,
            'cost_credits' => $chargedCredits,
        ]);
```

**Hinweis:** Der finale Return-Block (`return ['ok' => true, 'message' => ..., 'credits' => $newCredits];`) braucht KEINE Änderung — `$newCredits` wird bereits nach dem `DB::transaction(...)`-Aufruf frisch aus der DB gelesen (`DB::table('user_resources')->where('user_id', $userId)->value('credits')`) und spiegelt damit automatisch den rabattierten Abzug wider.

- [ ] **Step 4: Tests laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit --filter MerchantServiceTest`
Expected: PASS (alle bisherigen + die 2 neuen)

- [ ] **Step 5: Larastan prüfen**

Run: `bin/phpstan analyse app/Services/MerchantService.php --no-progress`
Expected: `[OK] No errors`

- [ ] **Step 6: Commit**

```bash
git add app/Services/MerchantService.php tests/Feature/MerchantServiceTest.php
git commit -m "feat: Handelsposten-Rabatt in MerchantService::buyItem() verdrahten"
```

---

### Task 5: Rabatt in `CorporateContactService` verdrahten (TDD)

**Files:**
- Modify: `app/Services/CorporateContactService.php`
- Test: `tests/Feature/Colony/CorporateContactServiceTest.php`

**Interfaces:**
- Consumes: `TradingPostService::discountFor($colonyId, 'corporate_contact')` (Task 2).
- Produces: `getActiveOffer()`s `price`-Feld ist bei aktivem Kanal-Rabatt (Stufe 3) niedriger — `buyHarvesterOffer()` re-derived denselben Preis, bleibt also automatisch konsistent (bestehendes "single source of truth"-Designprinzip dieser Klasse, siehe Klassendoc).

- [ ] **Step 1: Fehlschlagende Tests schreiben**

In `tests/Feature/Colony/CorporateContactServiceTest.php`, am Ende der Klasse (vor der schließenden `}`) einfügen. Nutzt `self::OFFER_HIT_TICK` (=71, dokumentiert deterministisch: Preis rollt ohne Rabatt auf 495 Cr, siehe Klassendoc) — der `setUp()` der Klasse hat bereits CC-Lv3 gesetzt und eine Harvester-Instanz platziert, beides für den Offer-Gate nötig:

```php
    // ── Handelsposten-Rabatt (Design-Spec 2026-08-23) ──────────────────────────

    private function setTradingPostLevel(?int $level): void
    {
        DB::table('colony_buildings')->where('colony_id', self::COLONY_ID)->where('building_id', 55)->delete();

        if ($level !== null) {
            DB::table('colony_buildings')->insert([
                'colony_id' => self::COLONY_ID,
                'building_id' => 55,
                'instance_id' => 1,
                'level' => $level,
                'status_points' => 20,
                'ap_spend' => 0,
            ]);
        }
    }

    public function test_active_offer_price_is_discounted_with_trading_post_level_3(): void
    {
        $this->setTradingPostLevel(null);
        $offerWithoutDiscount = $this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);
        $this->assertNotNull($offerWithoutDiscount);
        $fullPrice = $offerWithoutDiscount['price'];

        $this->setTradingPostLevel(3); // Stufe 3 schaltet den Nexus/Corporate-Contact-Kanal frei
        $offerWithDiscount = $this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);
        $this->assertNotNull($offerWithDiscount);

        $discount = (float) config('buildings.tradingPost.merchant_price_bonus');
        $expectedPrice = (int) max(1, round($fullPrice * (1 - $discount)));
        $this->assertSame($expectedPrice, $offerWithDiscount['price'], 'active offer price must reflect the trading post discount at level 3');
        $this->assertLessThan($fullPrice, $offerWithDiscount['price'], 'discounted price must be strictly lower than the undiscounted price');
    }

    public function test_active_offer_price_has_no_discount_below_level_3(): void
    {
        $this->setTradingPostLevel(2); // Schwelle für diesen Kanal ist 3, Level 2 unlockt ihn noch nicht

        $offer = $this->service->getActiveOffer(self::COLONY_ID, self::USER_ID, self::OFFER_HIT_TICK);

        $this->assertNotNull($offer);
        $this->assertSame(495, $offer['price'], 'below the level-3 threshold, price must equal the documented undiscounted roll (495 Cr at OFFER_HIT_TICK)');
    }
```

- [ ] **Step 2: Tests laufen lassen, Fehlschlag bestätigen**

Run: `bin/phpunit --filter test_active_offer_price_is_discounted_with_trading_post_level_3`
Expected: FAIL — `$offerWithDiscount['price']` ist identisch zu `$fullPrice` (kein Rabatt angewendet).

- [ ] **Step 3: Rabatt verdrahten**

In `app/Services/CorporateContactService.php`, Konstruktor um `TradingPostService` ergänzen:

```php
    public function __construct(
        private readonly HarvesterEntitlementService $harvesterEntitlementService,
        private readonly TradingPostService $tradingPostService,
    ) {}
```

In `getActiveOffer(int $colonyId, int $userId, int $tick): ?array`, die letzte Zeile `return ['price' => $this->priceRoll($colonyId, $tick)];` ersetzen durch:

```php
        $price = $this->priceRoll($colonyId, $tick);

        // Handelsposten-Kanal-Rabatt (Design-Spec 2026-08-23) — Stufe 3 schaltet
        // den Nexus/Corporate-Contact-Kanal frei.
        $discount = $this->tradingPostService->discountFor($colonyId, 'corporate_contact');
        if ($discount > 0.0) {
            $price = (int) max(1, round($price * (1 - $discount)));
        }

        return ['price' => $price];
```

Da `buyHarvesterOffer()` den Preis über `getActiveOffer()` re-derived (nicht separat berechnet), ist keine weitere Änderung in `buyHarvesterOffer()` nötig — Anzeige- und Kaufpfad bleiben automatisch konsistent.

- [ ] **Step 4: Tests laufen lassen, Erfolg bestätigen**

Run: `bin/phpunit --filter CorporateContactServiceTest`
Expected: PASS (alle bisherigen + die 2 neuen)

- [ ] **Step 5: Larastan prüfen**

Run: `bin/phpstan analyse app/Services/CorporateContactService.php --no-progress`
Expected: `[OK] No errors`

- [ ] **Step 6: Commit**

```bash
git add app/Services/CorporateContactService.php tests/Feature/Colony/CorporateContactServiceTest.php
git commit -m "feat: Handelsposten-Rabatt in CorporateContactService verdrahten"
```

---

### Task 6: GDD-Abschnitt „Handelsposten" aktualisieren

**Files:**
- Modify: `docs/GDD.md` (Abschnitt „Handelsposten (tradingPost) — Mechanik", aktuell ca. Zeile 458-467)

**Interfaces:** keine (reine Doku).

- [ ] **Step 1: „Händlerkonditionen"-Absatz umschreiben**

Der aktuelle Absatz:

```
**Passiv — Händlerkonditionen:**
Der Reisende Händler bietet bei Anwesenheit eines Handelspostens bessere Preiskonditionen (Bonus auf Handelswert). Konkreter Wert nach Playtest kalibrieren (siehe `config/buildings.php`).
```

wird ersetzt durch:

```
**Passiv — Kanal-Rabatt (Design-Spec 2026-08-23):**
Jede Ausbaustufe schaltet einen zusätzlichen Handelskanal für einen Preisrabatt frei, kumulativ: Stufe I (Bekannter Gast) den Kanal Cantina-Zufallsangebote, Stufe II (Fester Kunde) zusätzlich den Reisenden Händler, Stufe III (Persönlicher Kontakt) zusätzlich Nexus/Corporate Contact (Orin). Beim Cantina-Kanal gilt: kein Stack-Effekt mit dem Konsul-Rang-Verhandlungsbonus — der Rabatt gilt nur für nicht verhandelte Angebote. Exakter Rabattsatz: `config/buildings.php` → `merchant_price_bonus`.
```

- [ ] **Step 2: Veralteten TODO-Balance-Hinweis anpassen**

Der aktuelle Satz `Handelswert-Bonus muss mit dem Konsul-Rang-System abgestimmt werden (kein Stack-Effekt wenn Konsul Experte + Handelsposten).` im `> **TODO Balance:**`-Absatz ist jetzt umgesetzt (siehe Task 3) — Satz entfernen, der Rest des TODO-Balance-Absatzes (Baukosten/Decay-Kalibrierung) bleibt unverändert stehen.

- [ ] **Step 3: Bekannte, NICHT in diesem Plan behobene Diskrepanz nicht anfassen**

Der Absatz „**Passiv — Konsul-Effizienz:**" (AP-Kostenreduktion für Trade-Orders) beschreibt einen Effekt, der in `config/buildings.php` für `tradingPost` nicht existiert (kein entsprechendes Config-Feld) — das ist vermutlich eine Verwechslung mit der bereits an anderer Stelle implementierten `trade`-Kenntnis-Domäneneffizienz (§13.3). **Nicht in diesem Plan beheben** — außerhalb des Scopes (dieser Plan behandelt ausschließlich den Kanal-Rabatt-Mechanismus). Kurz als Kommentar `<!-- TODO: Konsul-Effizienz-Absatz prüfen, evtl. Verwechslung mit trade-Kenntnis-Domäneneffizienz — separater Task -->` direkt vor dem Absatz einfügen, damit es beim nächsten GDD-Audit auffällt.

- [ ] **Step 4: Commit**

```bash
git add docs/GDD.md
git commit -m "docs: Handelsposten-GDD-Abschnitt an Kanal-Rabatt-Mechanik anpassen"
```

---

### Task 7: Gesamtabnahme

**Files:** keine neuen — Verifikations-Task.

- [ ] **Step 1: Volle Test-Suite laufen lassen**

Run: `bin/phpunit`
Expected: alle Tests PASS (bestehende + alle in Tasks 2-5 neu hinzugekommenen)

- [ ] **Step 2: Larastan über das ganze Projekt laufen lassen**

Run: `bin/phpstan analyse --no-progress`
Expected: `[OK] No errors`

- [ ] **Step 3: CHANGELOG-Eintrag ergänzen**

In `CHANGELOG.md`, unter dem aktuellen Tagesdatum (neue Sektion anlegen, falls der Tag noch keine hat, sonst bestehende Sektion ergänzen):

```
- Feat: Handelsposten (tradingPost) bekommt seine erste echte Spielwirkung — Kanal-Rabatt-Freischaltung je Ausbaustufe (I=Cantina, II=+Reisender Händler, III=+Nexus/Corporate Contact), kumulativ, kein Stack-Effekt mit dem Konsul-Rang-Verhandlungsbonus. Bisher komplett wirkungsloser `merchant_price_bonus`-Config-Wert wird jetzt tatsächlich angewendet. Neuer `TradingPostService`, verdrahtet in `BarService`/`MerchantService`/`CorporateContactService`.
```

- [ ] **Step 4: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs: CHANGELOG-Eintrag für Handelsposten-Kanal-Rabatte"
```

---

## Self-Review-Notiz (vom Planautor, nicht Teil der Ausführung)

- **Spec-Abdeckung:** Kanal-Schwellen I/II/III → Task 2. Verdrahtung in alle 3 bestehenden Handelskanäle → Task 3-5. Kein-Stack-Regel mit Konsul → Task 3 explizit getestet. GDD-Umschreibung → Task 6.
- **Bewusst nicht Teil dieses Plans** (siehe Haupt-Spec „Offene Folge-Tasks"): Zahlen-Kalibrierung des Rabattsatzes, die im GDD als Diskrepanz markierte „Konsul-Effizienz"-Behauptung (Task 6 Step 3 markiert sie nur, behebt sie nicht).
- **Cross-Task-Konsistenz geprüft:** Alle drei Verdrahtungs-Tasks (3-5) rufen exakt dieselbe `TradingPostService::discountFor()`-Signatur mit den exakt in Task 2 definierten Channel-Strings (`'bar'`, `'merchant'`, `'corporate_contact'`) auf — keine abweichenden String-Literale.
