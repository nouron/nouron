# ROADMAP T10: Regolith-Mächtigkeit + Credits-Rekalibrierung — Design-Spec

Stand 2026-09-27, **überarbeitet** nach Owner-Korrektur zur Grundrichtung (siehe §0a). Vorstufe für `game-developer`/`backend-coder` (TDD-Implementierung). Bezug: ROADMAP „T10 Credits-Wand Phase 2 / Harvester-Stillstand im Bot", A44-H1/H2-Spec (`docs/superpowers/plans/2026-09-27-a44-h1-h2-vorkommen-spec.md`), `config/game.php` (`harvester`, `advisor`, `credits`, `bar`, `merchant`, `run.tasks`), `config/missions.php`, `app/Services/BarService.php`, `app/Services/MerchantService.php`.

Diese Spec ändert **keinen Code, keine Config, keine Tests**. Sie enthält jetzt Vorschläge, die über reine Bot-Regeln hinausgehen (ein neuer/erweiterter Verkaufskanal) — das ist weiterhin nur Spezifikation, keine Implementierung.

## 0a. Owner-Korrektur (2026-09-27) — warum diese Fassung anders ist

Die erste Fassung dieser Spec hatte zwei Schwächen, die der Owner zurückgewiesen hat:

1. Sie skalierte nur die Mächtigkeit hoch (1,5×) und schlug eine neue Bot-Regel vor, die **mehr Credits-Missionen fliegt** — behandelte damit nur Symptome, nicht die eigentliche Geldfluss-Richtung.
2. Sie stufte den 2-Tile-Worst-Case (totales Regolith-Versiegen, P≈0,8%) als „akzeptabler seltener Roguelike-Ausreißer" ein — eine reine Zahlen-Bewertung. Der Owner weist darauf hin, dass ein komplett versiegendes Regolith-Vorkommen während eines Runs unter Umständen gar nicht zum Setting passt, unabhängig von der Wahrscheinlichkeit.

Kernaussage des Owners: **„Eher sollte man Regolith verkaufen um Credits zu bekommen, statt andersrum."** Das ist eine Struktur-Aussage, keine Zahlen-Aussage. Diese Fassung untersucht deshalb zuerst, was der Code tatsächlich an Handelsrichtung hergibt (§1), bevor irgendetwas an Zahlen gedreht wird.

## 1. Bestandsaufnahme: existiert ein Verkaufsmechanismus, und in welche Richtung?

Code gelesen: `app/Services/BarService.php`, `app/Services/MerchantService.php`, `config/game.php` (`bar.*`, `merchant.commodity.*`), `tests/Feature/Playtest/BotStrategy.php` (`barOfferCandidate()`, `buildBarterOffer()`, `buildCorvanBuyOffer()`).

Es gibt **drei separate Handelskanäle**, keinen davon hat der Bot bisher „Regolith gegen Credits verkaufen" genutzt — aus dem einfachen Grund, dass **kein Kanal das aktuell anbietet**:

| Kanal | Quelle | Richtung | Regolith beteiligt? |
|---|---|---|---|
| Generische Cantina-Gäste (`bar_offers`, `visit_id` NULL) | `BarService::buildBarterOffer()` | Tauscht zufällig zwischen `TRADEABLE = [regolith, compounds, organics]` — **niemals gegen Credits** | Ja, aber nur gegen Compounds/Organika, nie gegen Credits |
| Corvans strukturierter **Sell**-Lot | `MerchantService::generateCommodityOffers()`, `config('game.merchant.commodity')` | Kolonie gibt Ressource → bekommt Credits | **Nein** — hart auf `sell_resource_id = 5` (Organika) begrenzt. Code-Kommentar wörtlich: „Sell side: only Organika (resource_id=5) — the deliberately narrow scope from §4b, not a generic sell-everything channel." |
| Corvans strukturiertes **Buy**-Angebot | `BarService::buildCorvanBuyOffer()` | Kolonie gibt Credits → bekommt Ressource aus `TRADEABLE` (Regolith, Compounds oder — ab Konsul-Rang 3 — mit 50 % Bias — Compounds) | **Ja, explizit Regolith möglich** |

**Ergebnis:** Es existiert **kein** Regolith→Credits-Kanal im Spiel. Es existiert aber ein expliziter Credits→Regolith-Kanal (Corvans Buy-Angebot). Der Bot nutzt diesen Kanal aktiv: `accept_bar_offer` (Regelkette, keine Sonderregel nötig — es ist einfach „irgendein annehmbares Angebot", sortiert nach `get_resource_id = Credits` zuerst, aber ein Corvan-Buy-Angebot mit `give_resource_id = Credits, get_resource_id = Regolith` wird ganz normal akzeptiert, sobald `creditReserveGuardBlocks()` nicht greift, d. h. sobald genug Credits im Puffer sind). Das ist exakt der in der Root-Cause-Untersuchung beschriebene Pfad „Bot kauft bei Corvan Regolith nach, wenn der Harvester tot ist" — kein Bot-Bug, sondern die einzige im Code vorhandene Handelsrichtung.

**Die Grundrichtungskorrektur des Owners ist damit keine Fehlinterpretation eines bestehenden Mechanismus, sondern zeigt eine echte Lücke auf: der von ihm gewünschte Regolith→Credits-Fluss existiert schlicht noch nicht.** Er muss neu gebaut werden (Config- + ggf. kleiner Code-Eingriff in `MerchantService`/`BarService`), nicht nur als Bot-Regel angeflanscht werden.

### Warum ist Organika der einzige Sell-Kanal, nicht Regolith?

Der Code-Kommentar (`config/game.php` Zeile ~937) verweist auf „§4b" (vermutlich eine frühere A13/Handel-Spec) als Begründung für die bewusste Beschränkung auf Organika. Diese Spec kann diese frühere Design-Entscheidung nicht rückwirkend einsehen (keine passende Datei unter `docs/superpowers/` mit diesem Verweis gefunden), vermutet aber: Organika war zum Zeitpunkt jener Entscheidung die einzige Ressource mit verlässlichem Überschuss (Agrardom-Produktion abzüglich `foodNeed()`), während Regolith knapp und hart umkämpft war (Bau-/Levelup-Gate). Mit der jetzigen T10-Situation (Regolith-Bestand am Ende eines Laufs „üppig, 300–500", siehe Root-Cause-Befund) kehrt sich diese Prämisse für die Spätphase eines Laufs um — genau der Fall, den der Owner beheben will.

## 2. Neuer Vorschlag: struktureller Regolith→Credits-Verkaufskanal

**Empfehlung:** Corvans Sell-Lot-Mechanismus (`MerchantService::generateCommodityOffers()`) um Regolith als zweite sellbare Ressource erweitern — nicht die generische Cantina-Gästerotation (die tauscht ohnehin nie gegen Credits, dafür wäre ein separater Umbau nötig, der über den Scope von T10 hinausgeht).

### Vorgeschlagene Mechanik (Konfigurationsebene, für `game-developer`)

- `config('game.merchant.commodity')` von einem einzelnen `sell_resource_id` auf eine Liste umstellen (z. B. `'sell_resources' => [5 => [...Organika-Parameter...], 3 => [...Regolith-Parameter...]]`) oder — minimal-invasiver — einen zweiten Eintrag `regolith` mit eigenen Lot-/Preis-/Reserve-Parametern analog zur bestehenden Organika-Struktur ergänzen.
- **Preis:** `sell_price_per_unit` für Regolith sollte spürbar unter `game.bar.base_prices[3]` (aktuell 30, siehe Corvans Buy-Basispreis) liegen — analog zum bestehenden 30 %-Spread bei Organika (35 Verkauf vs. 50 Kauf-Basis) — sonst wird Kaufen-dann-Verkaufen zur Arbitrage. Konkreter Zahlenvorschlag: **~21 Cr/Einheit** (30 × 0,7), aber das ist eine reine Kalibrierungsfrage für `game-developer`, keine Owner-Entscheidung.
- **Reserve-Floor:** analog zu Organikas `sell_reserve_multiplier × foodNeed()` — für Regolith fehlt ein direktes Äquivalent zu `foodNeed()`. Vorschlag: Reserve = die T17-CC-Forschungsreserve (`ccResearchReserve()`, bereits im Bot als Konzept vorhanden, siehe `BotStrategy::fitsAboveCcReserve()`) plus ein fixer Baupuffer (z. B. die Kosten des günstigsten noch fehlenden Path-Gebäudes, analog zu `cheapestPendingPathBuildingCost()`). Das verhindert, dass ein Sell-Lot generiert wird, der die Kolonie direkt in einen Regolith-Bau-Engpass verkauft — ein Verkaufsangebot für eine Ressource, die man noch fürs Bauen braucht, wäre ein Design-Widerspruch.
- **Wann generiert:** nur wenn der Regolith-Bestand über der Reserve liegt UND (analog zur Organika-Regel) über einem Vielfachen des laufenden Bedarfs — konkretes Vielfaches braucht Kalibrierung, kein Owner-Entscheid.

### Bot-Regel-Konsequenz

Sobald dieser Kanal existiert, braucht es **keine neue Bot-Regel** dafür — `barOfferCandidate()` bevorzugt bereits jedes Angebot mit `get_resource_id = Credits` vor allen anderen (siehe `orderByRaw('CASE WHEN get_resource_id = ? THEN 0 ELSE 1 END', [RES_CREDITS])`), und `accept_bar_offer` prüft ohnehin jeden Sol alle aktiven Angebote. Ein neu generiertes „Regolith→Credits"-Sell-Lot würde vom bestehenden `accept_bar_offer`-Regelwerk automatisch aufgegriffen, sobald es im Angebot auftaucht — **der Fix ist ganz überwiegend eine Content-/Config-Erweiterung von `MerchantService`, nicht ein neues Bot-Verhalten.**

## 3. Der bisherige Vorschlag `dispatch_credit_mission` — jetzt zweitrangig, nicht verworfen

Die in der ersten Fassung entworfene Bot-Regel (Credits-Missionen aktiv dispatchen, Präferenzliste `mission_escort_convoy` etc.) bleibt technisch korrekt beschrieben, ändert aber ihren Rang:

- **Vorher:** Hauptempfehlung, um `task_credit_reserve` erreichbar zu machen.
- **Jetzt:** Sekundärer, unabhängiger Credits-Kanal — sinnvoll, weil er eine andere Ressource in Wert setzt (Schiffs-/AP-Kapazität statt Regolith-Überschuss) und weil er zusätzliche `trust_event`-Nebenwirkungen trägt (unterstützt `task_colony_prosperity`). Er sollte **nicht mehr als der primäre Hebel für `task_credit_reserve` verstanden werden** — dieser Rang geht jetzt an den neuen Regolith-Sell-Kanal (§2), weil der strukturell die vom Owner gewünschte Fließrichtung abbildet und nebenbei den bereits vorhandenen, bisher ungenutzten Regolith-Überschuss (300–500 am Laufende, Root-Cause-Befund) monetarisiert, statt zusätzliche Schiffskapazität zu binden.
- **Reihenfolge in der Regelkette, falls beide implementiert werden:** Der Sell-Kanal braucht keine eigene Dispatch-Regel (er läuft über das bestehende `accept_bar_offer`, siehe §2) und würde daher an seiner bisherigen Position in der Kette bleiben. `dispatch_credit_mission` (falls implementiert) bliebe an der in der Erstfassung vorgeschlagenen Position (nach `dispatch_compounds_mission`, vor `relocate_harvester`) — aber als **Ergänzung für Läufe mit wenig Regolith-Überschuss**, nicht als Hauptfix. Die genaue Präferenzliste, Trigger-Schwelle (`CREDITS_SCARCE_BELOW`) und Priorität-Begründung aus der Erstfassung bleiben unverändert gültig als Spezifikation, falls der Owner beide Fixes will; siehe Anhang A für den vollständigen Wortlaut.

## 4. Neubewertung der Mächtigkeits-Skalierung (bisher 1,5×: 240/450/660)

**Kernfrage laut Auftrag:** Braucht es die Skalierung noch in dieser Höhe, wenn der Hauptfix jetzt „Regolith verkaufen" statt „mehr Credits-Missionen" ist?

**Antwort: Die beiden Probleme sind nicht dasselbe Problem und die Sell-Kanal-Lösung ersetzt die Mächtigkeits-Skalierung nicht.**

- Die Mächtigkeits-Skalierung adressiert: *Regolith als Baustoff* — ob genug Rohmaterial aus der Karte kommt, um Gebäude zu bauen/leveln, über die gesamte Lauflänge.
- Der Sell-Kanal adressiert: *Regolith als Handelsware* — ob ein bereits vorhandener Überschuss (der die Baubedürfnisse längst übersteigt) in Credits verwandelt werden kann, statt ungenutzt liegen zu bleiben.
- Ein Sell-Kanal **erzeugt keinen zusätzlichen Regolith auf der Karte**. Er wandelt nur um, was schon da ist. Die Karte liefert weiterhin nur das fixe Budget (Owner-Vorgabe: kein zusätzlicher Ring). Wenn die Mächtigkeit zu niedrig bleibt, bricht weiterhin der Bau-Nachschub — der Sell-Kanal kann das nicht kompensieren, er kann höchstens verhindern, dass ein *vorhandener* Überschuss brachliegt.

**Aber:** die Dringlichkeit einer aggressiven Skalierung (2× oder mehr, siehe die in der Erstfassung offene Frage 1) sinkt, weil ein Teil des ursprünglichen Schadensbildes (Root-Cause: „Regolith-Bestand am Ende üppig, 300–500, aber nutzlos") gar nicht durch Mangel entstand, sondern durch einen fehlenden Verwertungskanal — das ist ein Symptom, das die Sell-Kanal-Lösung behebt, ohne dass mehr Mächtigkeit nötig wäre. Die Fälle, die tatsächlich Mächtigkeit brauchen, sind die, in denen die Kolonie *während* des Laufs (nicht erst am Ende) ohne Regolith dasteht und nicht weiterbauen kann (Seed 7: Sol 62–99 ohne Ertrag) — das bleibt unverändert ein reines Kartenbudget-/Mächtigkeits-Problem, das der Sell-Kanal nicht berührt.

**Empfehlung:** 1,5× (240/450/660) als Zielgröße beibehalten — sie war ohnehin am beobachteten Fall (Seed 7, Sol 62 → Zielkorridor Sol 85–95) hergeleitet, nicht an der „üppiger Restbestand"-Beobachtung, die jetzt separat durch den Sell-Kanal behandelt wird. Eine zusätzliche Erhöhung *wegen* des Verkaufs-Arguments wäre nicht gerechtfertigt (das würde zwei unabhängige Stellschrauben vermischen); die ursprüngliche Herleitungstabelle in §1 der Erstfassung bleibt gültig (siehe Anhang B).

## 5. Der 2-Tile-Worst-Case — neu eingeordnet als GDD-Design-Frage, nicht nur Kalibrierung

Das ist der Kern der Owner-Korrektur. Zwei Fragen sind zu trennen:

### 5a. Ändert der Sell-Kanal etwas am Worst-Case selbst?

Nein, nicht direkt. Der Worst-Case (nur 2 H2-Tiles gefunden, P≈0,8%, Rush-Tile erschöpft nach ~8 Solen, Steady-Tile nach zusätzlichen ~41 Solen bei sofortiger Nutzung, siehe Erstfassungs-Tabelle) bleibt ein Fall, in dem die Karte schlicht kein weiteres Regolith mehr hergibt — der Sell-Kanal kann nur verkaufen, was da ist, nicht mehr herbeizaubern.

### 5b. Aber die Bedeutung des Worst-Case ändert sich, wenn Regolith→Credits der Haupt-Geldfluss wird

Das ist der Punkt, den die Owner-Korrektur eigentlich macht, und er ist wichtig: **In der bisherigen (fehlerhaften) Ausgangslage bedeutete „kein Regolith mehr" potenziell „Baustopp UND kein Weg, das zu kompensieren, außer Credits→Regolith zu kaufen — was zirkulär ist, wenn die Credits selbst knapp sind."** Das war der eigentliche Systembruch, den der Owner witterte, auch ohne ihn technisch exakt zu benennen: die einzige Rückfalloption bei Regolith-Mangel war, Regolith mit Credits zu kaufen, aber die einzige Credits-Quelle im Bot-Verhalten war (vor T10) praktisch nicht vorhanden — ein Teufelskreis ohne Ausweg.

Mit dem neuen Sell-Kanal (§2) und der Bot-Priorität, die daraus folgt, kehrt sich die Bedeutung von „Regolith ist leer" um: Es bedeutet dann nicht mehr „Notfall, keine Ressource mehr zum Handeln", sondern **„nichts mehr zum Verkaufen — die bisher aufgebauten Credits-Reserven und Gebäude bleiben bestehen, nur der Regolith-Einkommenskanal versiegt."** Ob eine Kolonie zu diesem Zeitpunkt noch weiterbauen kann, hängt davon ab, ob sie zu diesem späten Zeitpunkt überhaupt noch Regolith-kostende Bauschritte offen hat — und laut Pacing-Ziel (`project_pacing_targets_and_build_cost_rule`: Phase 1 Sol 15–20, Sieg 70–95) sollte der Großteil der Regolith-intensiven Bauarbeit (Phase 1 + frühe Phase 2) längst abgeschlossen sein, wenn eine Regolith-Ader um Sol 50–90 versiegt. Ein Versiegen **vor** Sol ~30 (während Phase 1 oder früher Phase 2) wäre dagegen tatsächlich ein Setting-/Balance-Problem — die Kolonie stünde ohne fertige Grundinfrastruktur UND ohne Verkaufsware da.

**Das ist die eigentliche, über reine Kalibrierung hinausgehende Design-Frage, die hiermit explizit an den Owner zurückgegeben wird:**

> Was passiert mechanisch, wenn eine Kolonie gar kein Regolith mehr bekommen kann — weder aus der Karte (alle Tiles erschöpft/keine weiteren erreichbar) noch aus Vorräten? Drei denkbare Antworten, keine davon ist in dieser Spec entschieden:
> 1. **Kein Sonderfall:** Die Kolonie spielt eingeschränkt weiter — kein Regolith-kostender Bauschritt mehr möglich, aber bestehende Gebäude, Credits-Wirtschaft (inkl. des neuen Sell-Kanals, solange noch Restbestand da ist) und alle nicht-Regolith-Mechaniken laufen normal weiter. Das ist der aktuelle De-facto-Zustand (kein Fail-State-Trigger existiert dafür, siehe `config('game.run.tasks')` — nur `nexus_debt_fail_threshold` und `phase1_deadline_sol` sind harte Fails) und würde am wenigsten Aufwand bedeuten.
> 2. **Später-Spiel-Nachschub-Mechanismus:** Ein separater, schwächerer Regolith-Zufluss (z. B. eine späte, seltene Mission oder ein dritter, kleinerer garantierter Fund), der sicherstellt, dass eine Kolonie nie vollständig auf 0 fällt, selbst im Worst-Case. Würde den Setting-Bruch direkt beheben, aber der Owner hat „keine weiteren Frontier-Tiles" bereits als Vorgabe ausgeschlossen — ein neuer Mechanismus müsste also außerhalb der Kartenmächtigkeit ansetzen (z. B. eine Cantina-/Corvan-Rarität, die Regolith statt Credits als Belohnung bringt — das wäre allerdings erneut ein Credits→Regolith-artiger Fluss, nur über eine andere Ressource/Aktion finanziert, und stünde in Spannung zur Grundrichtungskorrektur dieser Spec).
> 3. **Bewusster Setting-Kommentar:** Ein komplett erschöpftes Vorkommen wird explizit als Later-Game-Zustand der Kolonie erzählt/geframt (Nexus-Funk-Meldung, Codex-Eintrag: „die lokale Ader ist erschöpft") statt stillschweigend hingenommen — reine Content-/Text-Arbeit (`content-writer`), keine Mechanik-Änderung, behebt aber die vom Owner angesprochene Setting-Kohärenz kostengünstig.

Diese Spec empfiehlt **Option 1 kombiniert mit Option 3** als pragmatischsten Pfad (kein neuer Mechanismus, aber die Situation wird erzählerisch abgefangen statt stillschweigend als Leerlauf zu erscheinen) — **das ist jedoch ausdrücklich ein Vorschlag, keine Owner-Entscheidung.** Die Entscheidung, ob Option 1/2/3 (oder eine Kombination) gewählt wird, sollte vor der Implementierung von §2 eingeholt werden, weil sie beeinflusst, ob der Sell-Kanal (§2) eine Reserve-Floor-Regel braucht, die *verhindert*, dass ein Sell-Lot die letzten Regolith-Einheiten vor Laufende verkauft (sonst verschärft der neue Kanal genau das Szenario, das er eigentlich entschärfen soll — eine Kolonie verkauft sich in den Total-Mangel, weil ein Angebot gerade attraktiv aussah).

## 6. Bot-Batch-Empfehlung (angepasst)

Ein gemeinsamer Batch bleibt sinnvoll, aber der Scope hat sich verschoben: **Priorität 1 ist die Verifikation des neuen Sell-Kanals** (§2, sobald implementiert) — misst, ob `task_credit_reserve` jetzt ohne `dispatch_credit_mission` erreichbar wird, rein durch Monetarisierung von Regolith-Überschuss. Erst wenn das nicht ausreicht, lohnt sich die zusätzliche `dispatch_credit_mission`-Regel (§3) im selben oder einem Folge-Batch. Seed-Zahl weiterhin 8–12, `default`- und `focus`-Profil, Owner wählt die genaue Zahl (siehe `feedback_batch_duration_estimate_choice`).

## 7. Offene Owner-Fragen (überarbeitet)

1. **Regolith-Sell-Kanal (§2):** Zustimmung zur Grundidee (Corvans Sell-Lot-Mechanismus um Regolith erweitern)? Falls ja: Preis (~21 Cr/Einheit als Vorschlag) und Reserve-Floor-Formel sind `game-developer`-Kalibrierungsdetails, keine Owner-Entscheidung — aber **ob überhaupt ein zweiter Sell-Kanal neben Organika gebaut wird, statt z. B. die generische Cantina-Gästerotation um eine Credits-Option zu erweitern**, ist eine Architekturfrage, die der Owner absegnen sollte, bevor `game-developer` startet.
2. **2-Tile-Worst-Case / Setting-Frage (§5b):** Welche der drei skizzierten Optionen (kein Sonderfall / Später-Spiel-Nachschub / narrativer Kommentar) — oder eine Kombination — ist gewollt? Diese Entscheidung sollte **vor** der Sell-Kanal-Implementierung fallen, weil sie die Reserve-Floor-Logik des Sell-Kanals beeinflusst (siehe Warnung Ende §5b).
3. **Mächtigkeits-Skalierungsfaktor (unverändert aus Erstfassung):** 1,5× (240/450/660) bleibt empfohlen für das Baustoff-Problem (§4) — unabhängig von der Sell-Kanal-Frage. Gilt weiterhin: falls der Owner eher den Worst-Case selbst absichern will (mehr Mächtigkeit, nicht nur mehr Verkauf), ist ein größerer Faktor separat zu entscheiden.
4. **`dispatch_credit_mission` (§3):** Trotz reduzierter Priorität weiterhin bauen, oder erst nach Sell-Kanal-Batch-Ergebnis entscheiden, ob überhaupt nötig? Diese Spec empfiehlt Letzteres (erst messen, ob der Sell-Kanal allein reicht).

## Anhang A: `dispatch_credit_mission`-Spezifikation (unverändert aus Erstfassung, jetzt Sekundärfix)

**Trigger-Schwelle:** `CREDITS_SCARCE_BELOW = 2000` (Herleitung: beobachtete Credits-Plateaus 1.590–2.490 über gesamte Läufe, siehe Batch 2026-09-26; die Hälfte von `task_credit_reserve.threshold` = 4000).

**Präferenzliste (analog `REGOLITH_MISSIONS`):**

```php
private const CREDITS_MISSIONS = [
    ['mission_escort_convoy', 'normal'],    // corvette, ungegatet, ~47 Cr/Tick
    ['mission_courier_run', 'easy'],        // drone, ungegatet, ~45 Cr/Tick
    ['mission_trade_convoy', 'normal'],     // freighter, knowledge.trade>=1, ~43 Cr/Tick + trust_event
    ['mission_perimeter_patrol', 'normal'], // corvette, knowledge.defense>=1, ~37 Cr/Tick + trust_event
    ['mission_aid_transport', 'easy'],      // freighter, ungegatet (kostet 10 Organika), ~23 Cr/Tick + trust_event, Fallback
];
```

Platzierung in der Regelkette (falls implementiert): nach `dispatch_compounds_mission`, vor `relocate_harvester` — Begründung unverändert aus Erstfassung (Regolith/Compounds gaten Bauschritte härter als Credits punktuelle Aktionen gaten).

## Anhang B: Mächtigkeits-Tiers (unverändert aus Erstfassung)

| Tier | Alt (Rg gesamt) | Neu (Rg gesamt, ×1,5) |
|---|---|---|
| d1 (gering) | 160 | **240** |
| d2 (mittel) | 300 | **450** |
| d3 (groß) | 440 | **660** |

| tile_type | Gehalt (Rg/Sol) | Mächtigkeit (Rg) | Sole bis Erschöpfung |
|---|---|---|---|
| `regolith_y1_d1` | 16 | 240 | 15 |
| `regolith_y1_d2` | 16 | 450 | 28 |
| `regolith_y1_d3` | 16 | 660 | 41 |
| `regolith_y2_d1` | 23 | 240 | 10 |
| `regolith_y2_d2` | 23 | 450 | 20 |
| `regolith_y2_d3` | 23 | 660 | 29 |
| `regolith_y3_d1` | 30 | 240 | 8 |
| `regolith_y3_d2` | 30 | 450 | 15 |
| ~~`regolith_y3_d3`~~ | ~~30~~ | ~~660~~ | ausgeschlossen (Invariante b) |

Worst-Case-Rechnung (2 H2-Tiles): Rush (30 Rg/Sol, 240 Rg) endet nach ~8 Solen, Steady (16 Rg/Sol, 660 Rg) endet danach nach weiteren ~41 Solen — Total ~49 Sole Betrieb ab erstem Fund, danach strukturell kein weiterer Regolith-Zufluss aus der Karte. Bewertung dieses Falls jetzt in §5, nicht mehr als reine Zahlenfrage.

## Owner-Entscheidung 2026-09-28 (final)

Der Owner hat die in §2/§7 dieser Spec offen gelassene Architekturfrage final entschieden — abweichend vom in §2 empfohlenen Ansatz ("Corvans Sell-Lot-Mechanismus um Regolith erweitern"):

> „Corvan bietet kein Regolith an. Stattdessen können über die anderen Charaktere in der Bar bestimmte Handels-/Tauschangebote Regolith enthalten — seltener als die normale Ressourcen-Rotation, das ist so gewünscht."

**Umgesetzt (game-developer, PR feat/t10-regolith-sellchannel):**

1. **Corvans beide Mechanismen bleiben unverändert.** Nach einer Zwischenkorrektur des Owners noch am selben Tag wurde klargestellt: `buildCorvanBuyOffer()` (Corvan verkauft Ressourcen — inkl. Regolith — an den Spieler gegen Credits) ist NICHT betroffen und bleibt exakt wie im Bestandscode. Die Owner-Aussage "Corvan bietet kein Regolith an" bezog sich ausschließlich auf Corvans separaten, strukturierten Sell-Lot-Kanal (`game.merchant.commodity`, Spieler verkauft AN Corvan) — der bleibt wie in Spec v2 hart auf Organika beschränkt, unverändert.
2. **Neuer, seltener Regolith→Credits-Verkaufsangebot-Typ in der generischen Gäste-Rotation** (`BarService::generateOffersForColony()`), NICHT über Corvan/`MerchantService`. Neue Methode `BarService::buildRegolithSellOffer()`, gated durch neue Config `game.bar.regolith_sell_offer_chance_pct` = 12 (12% pro Gast-Slot, klar unter der 88%-Basisrate normaler Tauschangebote). Preisformel mirrort `buildCorvanBuyOffer()`s Muster: `base_prices[Regolith]` (25) ± `price_variance` (±20%), Losgröße 10–30 (Schritt 5, wie `buildBarterOffer()`). Keine explizite Reserve-Floor-Regel ergänzt (§5b bleibt formal offen, aber die Bot-Regel `barOfferCandidate()` respektiert bereits die CC-Forschungsreserve für alle Regolith-gebenden Angebote, siehe unten).
3. **Mächtigkeits-Skalierung** `harvester.resource_max` (d1/d2/d3) 160/300/440 → 240/450/660 wie in Spec v2 §4/Anhang B hergeleitet, unverändert übernommen. `fresh_yield`-Gehaltstiers unverändert.
4. **Aus Spec v2 NICHT umgesetzt:** `dispatch_credit_mission` (Anhang A) und die ursprüngliche "Corvan-Sell-Lot erweitern"-Variante (§2) — beide durch die finale Entscheidung überholt/verworfen.

**Bot-Verifikation:** `BotStrategy::barOfferCandidate()` priorisiert bereits jedes Angebot mit `get_resource_id = Credits` (`ORDER BY CASE WHEN get_resource_id = Credits THEN 0 ELSE 1`) und respektiert die CC-Forschungsreserve für `give_resource_id = Regolith` unverändert — das neue seltene Angebot wird ohne weitere Bot-Änderung automatisch aufgegriffen, sobald es generiert wird. Keine neue Bot-Regel nötig.

**Offen für einen Folge-Batch (nicht Teil dieser Umsetzung):** ob `regolith_sell_offer_chance_pct` = 12% und die Preisformel in der Praxis (8–12 Seeds, `default`+`focus`) tatsächlich messbar zu `task_credit_reserve` beitragen, oder ob Frequenz/Preis nachjustiert werden müssen. Owner entscheidet Batch-Größe.
