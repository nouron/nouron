# Changelog

## 2026-04-16 (Implementierung: Regolith eingeführt, Tradecenter entfernt)

- **Regolith (Rg)** als primäre lokale Bergbau-Ressource eingeführt: Ressource ID 3 von `res_water` auf `res_regolith` umbenannt, Startwert 200, handelbar. DB-Migration + OnboardingService angepasst.
- **Industriemine** produziert jetzt Regolith (resource_id 3) statt Ferum/Werkstoffe — Config, Seeder und Testdata angepasst.
- **Tradecenter (building ID 43) entfernt**: aus config/buildings.php, MasterDataSeeder, Migration, Lang-Dateien und testdata.sqlite.sql gelöscht. Trader-Berater und Wirtschafts-Forschung erfordern jetzt Bar (ID 52) als Voraussetzung.

## 2026-04-15 (Design: Flottenbewegung — interstellare Bewegung bewusst nicht implementiert)

- **Interstellare Bewegung gestrichen** (GDD §8, ROADMAP): Flotten operieren ausschließlich im eigenen System — bewusste Designentscheidung, kein vergessenes Feature.
- **Sprungtor als narratives Element** definiert: im System sichtbar, nicht nutzbar, bewachbar (`defend`-Order). Verbindung zur Nexus-Lore (warum siedelt Nexus hier?).
- **"Gäste von außerhalb"** kommen via Events und Bar — keine Bewegungsmechanik nötig.
- ROADMAP: Flottenbewegung als erledigt markiert, interstellare Bewegung in "Bewusste Designentscheidungen"-Tabelle aufgenommen.

## 2026-04-15 (Design: Handelsmechanik — Bar als einziger Handelsort, Nexus-Fallback, Lore)

- **Handelssystem komplett redesignt** (GDD §11): Bar/Cantina ist der einzige Handelsort — NPC-Gäste, Spieler-zu-Spieler, Kauf und Verkauf alles über dieselbe Mechanik.
- **Bar-Mechanik:** 0–2 Gäste pro Tick (RNG), Angebote 1–2 Ticks gültig, Credits-gegen-Ressource und Ressource-gegen-Ressource möglich. Spieler-Angebote erscheinen anonym als Gäste.
- **Nexus-Handelsschiffe** als garantierter Fallback (immer verfügbar, teuer, 3 Ticks Lieferzeit). Händler-Berater verbessert Preis und Lieferzeit auf beiden Kanälen.
- **Tradecenter gestrichen** (war CC Lv5, zu spät, ohne eigenständige Rolle).
- **Kenntnishandel entfällt** mit Freischalt-Modell; AP-Delegation als Phase-4-Idee dokumentiert.
- **Lore-Fundament** erstellt: Nouron = untergegangenes System, Nouronen = Hochkultur, Nexus = menschliche Expansionsinstanz. Narrativ-Referenz unter `docs/narrative/`.
- **Mission-Einleitungstext** (DE+EN) mit Nexus als Instanz ausgearbeitet.

## 2026-04-15 (Design: Ressourcen-Redesign — Regolith eingeführt, Werkstoffe nur Handel/Events)

- **Regolith (Rg)** als dritte handelbare Ressource eingeführt (GDD §3): lokal abbaubar durch Industriemine, primäres Baumaterial für Gebäude. Spieler startet mit 200 Rg (Frontier-Depot-Narrativ).
- **Werkstoffe (Co)** neu positioniert: nicht mehr lokal produzierbar, nur via KI-Händler, Spielerhandel und Events. Verwendungsdomäne: Schiffbau, High-Tech, Reparatur.
- **Industriemine** produziert jetzt Regolith statt Werkstoffe (GDD §5, config/game.php TODO).
- **Klare Ressourcen-Domänen** definiert: Regolith = Rohbau, Werkstoffe = High-Tech/Schiffe, Credits = Grundkosten überall.
- **Singleplayer-Sicherheitsnetz** dokumentiert: KI-Händler garantieren Werkstoffe-Verfügbarkeit; Events sind Bonus, kein Progression-Lock.

## 2026-04-14 (Design: Kenntnisse-Redesign — Freischalt-Techtree + Berater-Zuweisung)

- **Kenntnisse-System grundlegend neu designt** (GDD §10): Level+Decay-Modell wird durch Freischalt-Techtree ersetzt — Kenntnisse werden einmalig erarbeitet und bleiben permanent. Kein Decay auf Wissen.
- **Zwei Effekt-Ebenen** definiert: Primäreffekt (immer aktiv nach Freischaltung), Sekundäreffekt (aktiv wenn Kenntnis einem Berater zugewiesen).
- **Berater-Zuweisung** als neue Mechanik: jeder Berater ab Rang 2 hat 1 Kenntnis-Slot. Max. 5 aktive Sekundäreffekte gleichzeitig (einer pro Berater). Erzeugt echte Spezialisierungsentscheidungen.
- **Roguelike-Variabilität**: pro Run nur zufällige Teilmenge der Kenntnisse verfügbar (z.B. 5 von 7).
- **Roadmap Phase 3a** um drei Design-Punkte erweitert: Kenntnisse-Redesign, Handel-Redesign, Flottenbewegung-Redesign — je mit eigenem Branch.
- Vollständige Berater×Kenntnisse-Matrix (35 Kombinationen) und konkrete Sekundäreffekt-Werte sind TODOs nach erstem Playtest.

## 2026-04-12 (GDD-Review: Inkonsistenzen behoben, techs → knowledge umbenannt)

- **CC max_level 10 → 5** in GDD §4 korrigiert (war nur noch dort veraltet).
- **GDD §2 Tick-Schritt 7** vereinfacht: Formel durch Verweis auf §6 ersetzt (single source of truth).
- **Wohnhabitat max_level 200 → 6** (max 6 Instanzen); Voraussetzung CC Lv3 → CC Lv1 (Tutorial-Schritt).
- **Leveled vs. Instanced Buildings** als TODO in GDD §4 dokumentiert. Game-Designer-Evaluation: nur Wohnhabitat und Hangar sind Instanced, alle anderen Leveled.
- **§7 Decay** bereinigt: Einleitungstext "Schiffe verfallen" entfernt; Instanced-Building-Konsequenz (sofortige Zerstörung statt Level-Down) dokumentiert; Hangar combat_factor korrekt auf Hangar-Decay (nicht Schiffs-Decay) umgestellt; Notreparatur (Credits statt AP) für CC/Wohnhabitat definiert.
- **Fail State 1** neu definiert: "Kolonie unbewohnbar" (CC Lv0 oder alle Wohnhabitate zerstört) statt vagem "Supply = 0".
- **Supply-Startwert** in §3 auf 10 korrigiert (CC Lv1, 0 Wohnhabitate); §6 Startsituation angepasst.
- **`config/game.php`** bereinigt: `supply`-Block um `cap_commandcenter`, `cap_housingcomplex`, `knowledge_cap_per_level` ergänzt; `cost_advisor` entfernt; `combat.ship_power` auf 3 aktuelle Schiffstypen reduziert; `production`-Eintrag waterextractor entfernt; Kommentare aktualisiert.
- **`config/advisors.php`**: `supply_cost`-Key entfernt (Berater kosten kein Supply); `credits` auf 50 Cr kalibriert.
- **`config/buildings.php`**: Wohnhabitat `max_level` 200 → 6, Voraussetzung CC Lv1; Kommentare aktualisiert.
- **techs → knowledge umbenannt**: `config/techs.php` → `config/knowledge.php` mit 7 neuen Kenntnissen (construction, cartography, geology, agronomy, health, trade, defense, IDs 90–96); `lang/de/techs.php` und `lang/en/techs.php` umbenannt; `SyncTechs.php` → `SyncKnowledge.php`, Command `game:sync-techs` → `game:sync-knowledge`; `MoralService` aktualisiert.
- **GDD §11 Handel**: Restriktion vereinfacht — Handel ist immer öffentlich (nur Wert 0), Werte 1–3 abgekündigt.
- **GDD §13**: Moralreferenz "Schritt 8" → "Schritt 8b" korrigiert; Querverweis auf Kenntnisse-Moraleffekte in §10 ergänzt.
- **Sonderfall "Schiffe ohne Hangar"** als TODO in §6 notiert (Events/Handelsdeals als Roguelike-Element, Phase 4+).

## 2026-04-11 (AP-1: Balancing-Review — Supply-System, Kenntnisse, Credits)

- **CC max_level 10 → 5:** Kommandozentrale hat Hard-Cap Level 5 (max. 50 Supply-Cap). GDD und config/buildings.php korrigiert.
- **CC Supply-Formel: 10 pro Level** (statt pauschal 15): Startsituation CC Lv1 + 1 Wohnhabitat = 18 Supply-Cap (vorher 23). Engerer Einstieg, stärkerer Anreiz für CC-Ausbau.
- **Kenntnisse als Supply-Cap-Quelle:** Kenntnisse kosten kein Supply mehr — sie erhöhen den Cap. Nicht-lineare Progression pro Level: +3/+5/+5/+4/+3 (total 20 pro Kenntnis, 7 × 20 = 140 max). Konfiguriert in `config/game.php → supply.knowledge_cap_per_level`. Hard-Cap 200 bleibt erreichbar, erfordert aber signifikante Investition in Breite (alle Kenntnisse Lv3 > wenige Kenntnisse Lv5).
- **Credits-Einnahmen dokumentiert (GDD §3):** Vier Quellen: Kolonistensteuern, Galaktischer Rat (staatliche Subventionen, Name TBD), Handel, Events.
- **Hangar-Decay-Konsequenz definiert (GDD §7):** Verfallener Hangar macht zugewiesenes Schiff unbrauchbar (nicht zerstört). Reparatur des Hangars reaktiviert das Schiff. Schiff bleibt in der DB erhalten.

## 2026-04-10 (Berater-System: Slot-Implementierung, Stratege, Kommandanten-UI)

- **Berater-System: Slot-System implementiert.** GDD §12 und Implementierung auf einen Stand gebracht: max. 1 Berater pro Typ pro Kolonie (UNIQUE INDEX), CC-Level schaltet Slots frei (CC Lv1 = 1 Slot, max. 5). Berater kosten jetzt korrekt Credits statt Supply — Bug in `PersonellService::hire()` behoben.
- **Stratege als 5. Beratertyp eingeführt.** DB-Migration, Config-Eintrag (`strategy`-AP-Pool), `PersonellService::resolveType()` und View-Karte ergänzt.
- **Kommandanten-UI:** Piloten können nun über die Berater-Seite Flotten zugewiesen und abberufen werden (`assignCommander`/`unassignCommander`). Die Service-Methoden existierten bereits, Route und UI fehlten noch.
- **Testdaten bereinigt:** Von bis zu 19 Beratern pro Typ auf je 1 pro Typ reduziert (entspricht dem Slot-System). Stratege in beide Kolonien aufgenommen.
- **GDD §12 aktualisiert** (game-designer): Individuen-Prinzip explizit formuliert, Typenbezeichnungen vereinheitlicht, Rang-Tabelle (Junior/Senior/Experte), Credits-Kosten, TODO Kommandanten-UI dokumentiert.

## 2026-04-10 (Design-Sprint Phase 3: Gebäude, Schiffe, Kenntnisse, GDD-Review)

- **Gebäude 25 → 12:** Stark reduziert auf Mini-4X-Kernsortiment. Neue Namen (Cantina, Agrardom, Industriemine, Kolonialdenkmal etc.). Raumwerft + Kampfwerft → **Hangar** (1 Gebäude = 1 Schiffsslot, Supply-begrenzt). Wasser als Ressource gestrichen (durch Versorgung abstrahiert), Wasserextraktor entfernt. Englische Sprachdateien (`lang/en/`) neu erstellt.
- **Schiffe 6 → 3:** Sonde (unbemannt, kein Supply), Korvette (ex Fighter, 14 Supply), Frachter (ex Transporter, 6 Supply). Ship-Decay abgeschafft — Schiffe werden durch Kampf/Gefahren zerstört, nicht durch Verfall. Hangar-Decay ersetzt den Wartungsdruck.
- **Kenntnisse (ex Forschungen) 10 → 7:** Umbenennung von "Forschungen" zu "Kenntnisse" — praktisches Kolonialwissen statt akademische Wissenschaft. Doppelnamen: Bautechnik & Materialverarbeitung, Kartografie & Erkundung, Geologie & Rohstoffgewinnung, Agronomie & Kultivierung, Gesundheit & Wohlbefinden, Handel & Logistik, Verteidigung & Überlebenstaktik.
- **GDD §1 neu geschrieben:** Singleplayer-Koloniekommandant mit Roguelike-Elementen. Vorbilder um FTL und Catan erweitert. Diplomatie/Politik als USP gestrichen.
- **GDD-Review durch 3 Game-Designer-Agenten:** 15 Inkonsistenzen gefunden und behoben (§4–§14). Veraltete Schiffe, Gebäude, Ressourcen und Forschungs-Keys durchgehend ersetzt.
- **Berater-Cap durch CC-Level:** CC Lv1–5 erlaubt 1–5 Berater. Berater kosten Credits, kein Supply — Widerspruch zwischen §6 und §12 aufgelöst.
- **Phase-1-Bedingung "Supply > 0" entfernt** (trivial, da Supply nie spontan auf 0 fällt).

## 2026-04-09 (Design-Sprint Phase 3: Berater- und AP-System)

- **Berater-Redesign (GDD §12):** 4 Typen → 5 Typen. Neue Namen: Baumeister, Analytiker, Raumfahrer, Stratege (neu, MilitärAP), Konsul. Navigation und Militär wurden als separate AP-Typen aufgespalten; Handel und Diplomatie unter Konsul/Handel zusammengeführt.
- **AP-System:** 5 Typen mit Kurznamen (Konstruktion, Analyse, Navigation, Strategie, Handel). Grundwert 6 AP/Tick auch ohne Berater. Max. 5 Berater (einer pro Typ). Level 1–5 mit Sweet Spot bei Level 4.
- **Upkeep-Mechanik:** Berater kosten Credits/Tick statt Supply. Supply bleibt Kapazitätsdeckel für Gebäude und Schiffe — sauberere konzeptionelle Trennung.

## 2026-04-08 (Design-Sprint Phase 3: Spielkonzept und Ressourcen)

- **Spielkonzept neu ausgerichtet:** Nouron wird von einem 4X-Multiplayer-Online-Spiel zu einem Singleplayer-Roguelike-Mini-4X (FTL/Catan-Stil) umgebaut. Keine Rassen mehr — nur Fraktionen. Kein Battlecruiser/Kreuzer. Async Spielerinteraktion (Forge-of-Empires-Stil).
- **Run-Struktur definiert (GDD §14):** Jeder Run = Expeditionsmission mit 2 Phasen. Phase 1: Kolonie stabilisieren (CC Lv3, Supply stabil, 3 Berater). Phase 2: 2 von 3 zufälligen Aufgaben aus einem 10er-Pool erfüllen (Tick-Limit 100). Fail States: Versorgungskollaps oder Zeitablauf.
- **Ressourcenliste überarbeitet (GDD §3):** ENrg/LNrg/ANrg (rassenspezifisch) abgekündigt. Neue Liste: Credits (Cr), Versorgung (Sup), Wasser (W), Werkstoffe (Co), Organika (Or), Moral (M). Kürzel basieren auf englischen Namen für Sprachunabhängigkeit. Exotics als 4. handelbarer Rohstoff für Phase 4+ reserviert.

## 2026-04-07 (AP-0: DB-Reset und Test-Isolation)

- **`php artisan db:reset`:** Neuer Artisan-Command (AP-0a) — löscht alle Tabellen, führt Migrations aus, befüllt mit Simpsons-Testdaten (via `TestSeeder`). Bestätigungsprompt schützt vor versehentlichem Aufruf; `--force` überspringt ihn.
- **Test-Isolation fix (AP-0b):** `phpunit.xml` nutzte `DB_DATABASE=:memory:` ohne `force="true"` — `.env.testing` überschrieb den Wert mit `test.db` (Datei). Durch Ergänzung von `force="true"` laufen Tests jetzt korrekt gegen In-Memory-SQLite. `test.db` wird nach einem Testdurchlauf nicht mehr verändert.

## 2026-04-06 (QA-Tests: Ownership, Trade-Clamp, Colony-Rename, Auth-Throttle)

- **4 neue Feature-Testklassen** mit insgesamt 40 Tests für zuvor unabgedeckte Phase-2-QA-Befunde (CRIT-1, HIGH-2, HIGH-4, MED-3, LOW-1).
- **CRIT-1 / HIGH-4 (`FleetTransferOwnershipTest`):** `addToFleet`-Endpoint liefert 403 für fremde Flotten; `convoy`/`join`-Orders auf fremde Zielflotten werden mit Validierungsfehler abgelehnt. Happy-Path (eigene Flotte) jeweils abgedeckt.
- **HIGH-2 (`TradeOrderResourceClampTest`):** `game:tick` Trade-Orders clampen korrekt auf Quellbestand — Fleet gibt nicht mehr ab als vorhanden, Colony nicht mehr als sie hat. Dabei wurde ein Bug in `GameTick::transferResource` behoben: `FleetResource::increment()` schlug wegen fehlendem Einzel-PK (Composite-Key) lautlos fehl; ersetzt durch direktes `DB::table->update/insert`.
- **MED-3 + Flash-Messenger (`ColonyRenameTest`):** HTML-Injection (Script-Tags, Angle-Brackets, Curly Braces) wird per Regex-Validierung abgelehnt; Grenzwerte (min 2, max 50) und Success-Flash-Message getestet.
- **LOW-1 + Auth-Flow (`LoginFlowTest`):** Login-Throttle (`throttle:5,1`) blockiert ab dem 6. Versuch mit 429; korrektes Passwort nach Lockout ebenfalls geblockt. Erfolgreicher Login mit Username und Email je separat abgedeckt.

## 2026-04-06 (Flotten auf Galaxiekarte)

- **Flotten auf Galaxiekarte:** `GalaxyController::getMapData()` liefert jetzt Layer-3-Einträge für alle Flotten im System-Sichtbereich (Radius 50). Eigene Flotten werden grün dargestellt, fremde gelb — galaxy.js war bereits vorbereitet, Farb-Logik per `obj.attribs.class` ergänzt. `Fleet`-Model um `user()`-Relation erweitert.
- **Skalierungsprüfung:** System-Radius 50 Einheiten; Speed 4 ≈ 12 Ticks, Speed 1 ≈ 50 Ticks — kein Anpassungsbedarf.

## 2026-04-06 (Colony-UI)

- **Colony-UI:** Neue Route `/colony` mit `ColonyController` und Blade-View. Zeigt Kolonienname, Position und Gründungs-Tick. Umbenennung über PATCH `/colony/name` mit Validierung (min 2, max 50 Zeichen). Schreibt direkt in `glx_colonies` (Colony-Model liest aus View `v_glx_colonies`).

## 2026-04-06 (README überarbeitet)

- **README:** Testaccounts-Tabelle (Bart/Homer/Marge), DB-Dateien-Übersicht (nouron.db vs. test.db), Artisan-Commands (`game:sync-techs`, `game:tick`), Scheduler-Cron-Snippet, WSL2-Hinweis für Windows-Entwickler, korrekter Test-Aufruf (`--testsuite=Feature`). Twitter-Link auf X aktualisiert, Facebook-Link auf HTTPS.

## 2026-04-06 (Granulare Bypass-Flags)

- **`config/game.php`:** Neuer `bypass`-Block mit drei unabhängigen Flags: `ap_checks`, `resource_costs`, `supply_checks` (je per `.env` steuerbar). Ermöglicht gezieltes Testen einzelner Systeme — z.B. AP-Verhalten testen während Ressourcenkosten deaktiviert bleiben.
- **`dev_mode` deprecated:** Bleibt als Legacy-Shortcut erhalten, wirft aber `E_USER_DEPRECATED` + Laravel-Log-Warning und expandiert sich in alle drei Bypass-Flags. Wird in einer späteren Version entfernt.
- **`AppServiceProvider::bootBypassFlags()`:** Verarbeitet Legacy-Expansion und enthält Production-Guard — aktive Bypass-Flags in Produktion werfen eine `RuntimeException`.
- **Alle Verwendungsstellen** auf `game.bypass.*` umgestellt (`FleetService`, `AbstractTechnologyService`, `PersonellService`, `TradeGateway`).
- **`.env`** nutzt jetzt `GAME_BYPASS_AP/RESOURCES/SUPPLY=true` statt `GAME_DEV_MODE=true`. **`.env.example`** dokumentiert alle Flags mit Test-Szenarien.

## 2026-04-06 (QA-Fixes Phase 2)

- **CRIT-1** `addToFleet`: Ownership-Check ergänzt — fremde Fleet-IDs werden mit 403 abgewiesen.
- **CRIT-2** AP-Check + AP-Lock in `FleetService::addOrder` in eine DB-Transaktion zusammengefasst (TOCTOU-Schutz).
- **HIGH-1** Off-by-one in `GalaxyService::getPath()`: `$path[$tick++][2]` → `$path[$tick][2]` (Slot-Wert am Zielpunkt wurde den Tick-Zähler fälschlich weitersetzen).
- **HIGH-2** `GameTick::transferResource()`: Menge wird jetzt auf verfügbaren Bestand der Quelle geklemmt, bevor DB-Updates erfolgen — verhindert Ressourcenerzeugung aus Luft.
- **HIGH-3** `processShipDecay()`: `FleetShip::all()` durch `chunkById(200, …)` ersetzt.
- **HIGH-4** `convoy`/`join` Orders: Zielflotte muss dem eigenen User gehören; `defend` bleibt offen (Allianz-Unterstützung legitim).
- **MED-1** `addResearchOffer()`: AP-Check und AP-Lock analog zu `addResourceOffer()` ergänzt.
- **MED-2** `config/game.php`: `dev_mode` Default auf `false` gesetzt; `.env` erhält `GAME_DEV_MODE=true` für die Dev-Umgebung.
- **MED-3** Colony-Rename: Regex-Validierung blockiert HTML/Script-Zeichen (`<>{}[]`).
- **MED-4** Combat-Events: Moral-Events und INNN-Events werden jetzt für alle beteiligten Defender-User gefeuert, nicht nur den ersten.
- **LOW-1** Login: `throttle:5,1` Middleware auf POST `/login`.
- **LOW-2** `getColoniesByCoords()`: hardcoded Radius 50 durch `getSystemViewRange()` ersetzt.
- **LOW-3** Tippfehler `devide` → `divide` in `FleetService::$validOrders`.
- **LOW-4** `getOrders()`: `orderByRaw()` durch Whitelist-geprüftes `orderBy()` ersetzt.

## 2026-04-06 (Roadmap, GDD und config/game.php abgeglichen)

- **ROADMAP.md aktualisiert:** Phase 1b als abgeschlossen markiert (April 2026). Prio-1-Bug (PersonellService), alle Prio-2-Items (Advisor-UI, Forschungshandel, Einstellungen) und Prio-3-Items (game:sync-techs, Scheduler, Fleet-Orders) als erledigt markiert. Interstellare Flottenbewegung aus Phase 2 entfernt (nur in Phase 3a).
- **GDD §7 (Decay):** Abschnitt "Schema-Konsequenzen (noch nicht implementiert)" entfernt und durch korrekte Beschreibung des implementierten Schemas ersetzt (max_status_points, decay_rate in Stammdaten; status_points in colony_buildings, fleet_ships, colony_researches).
- **GDD §2 (Tick-Schritte):** Schritt 8b (Moral Calculation) in der Tick-Tabelle ergänzt — war im §13 bereits dokumentiert, fehlte aber in der zentralen Übersicht.
- **GDD §8 (Fleet Orders):** AP-Kostentabelle um hold/join/convoy (je 1 AP), defend (2 AP) erweitert. Beide AP-Tabellen (§1.1 und §8) sind jetzt konsistent.
- **GDD §12 (Supply-Kosten):** "noch zu definieren"-Platzhalter durch tatsächliche Werte aus `config/buildings.php`, `config/ships.php` und `config/techs.php` ersetzt.
- **`config/game.php`:** `fleet.order_costs` um hold, join, convoy (1 AP), defend (2 AP) ergänzt (gemäß Designprinzip "Militarismus ist teuer").

## 2026-04-06 (Scheduler, game:sync-techs, Fleet-Orders vervollständigt)

- **`game:sync-techs`:** Neuer Artisan-Command synchronisiert `config/ships.php` → `ships`-Tabelle (moving_speed, decay_rate, supply_cost, max_status_points) und `config/buildings.php` → `buildings`-Tabelle (decay_rate, supply_cost, max_status_points, max_level). Zeigt je geänderter Row "alt → neu". `--dry-run`-Option zum Vorschauen. Erster Lauf hat 6 Ships mit `moving_speed=500` (Altlast) auf korrekte Werte gesetzt.
- **Laravel Scheduler aktiviert:** `routes/console.php` — `game:tick` läuft jetzt täglich um 03:00 Uhr automatisch (cron muss einmalig auf dem Server eingerichtet werden: `* * * * * php artisan schedule:run`).
- **Fleet-Orders vervollständigt:** `storeOrder`-Validator akzeptiert jetzt `hold`, `convoy`, `defend`, `join` zusätzlich zu `move`, `trade`, `attack`. Jeder neue Order-Typ hat eigene Controller-Logik und UI-Felder im Flotten-Konfigurationsformular. Befehlstyp-Dropdown mit `<optgroup>`-Struktur. Order-Beschreibungszeile unter Formular (zeigt was der Befehl tut).
- **Lokalisierung `lang/de/fleet.php`:** Neue Sprachdatei für alle Flotten-UI-Texte (Order-Namen, Feldbezeichnungen, Beschreibungen).

## 2026-04-06 (UI-Polishing: Berater, Handel)

- **Berater-Seite umgebaut:** Berater werden jetzt in 4 separaten Cards gruppiert (Ingenieure, Wissenschaftler, Händler, Kommandanten). Jede Card zeigt die eigene AP-Summe und Supply-Kosten im Footer. Auf Desktop 2 Cards nebeneinander. Flottenkommandanten werden mit Flottenname angezeigt; `AdvisorController` lädt jetzt zusätzlich alle `is_commander`-Advisors des Users über alle Flotten. AP-Gesamtübersicht durch kompakte Statuszeile ersetzt. Dicke Farbrahmen entfernt, neutrale Cards.
- **Trade-Seiten:** Rohstoff-Spalte zeigt jetzt `res-chip` mit Kürzel + Tooltip statt plain Text. Preis-Spalte zeigt Credits-Chip. Restriktions-Spalte zeigt lesbare Badges (Alle/Gruppe/Fraktion/Rasse) statt roher Integers. Tabellenzeilen farblich nach Richtung (Kauf/Verkauf). `table-striped` entfernt.
- **Shared Partial:** `resources/views/partials/res_chip.blade.php` — wiederverwendbarer Ressourcen-Chip, der dieselbe `.res-chip`-Klasse wie die Ressourcenleiste nutzt.

## 2026-04-06 (Forschungshandel-View, User-Einstellungen, Roadmap Phase 3–5)

- **Forschungshandel-View:** Neue Route `/trade/researches`, Controller-Methoden `researches()` und `addResearchOffer()` in `TradeController` ergänzt. View analog zu Rohstoff-Handel (Tabelle, Filter, Modal zum Erstellen). `TradeGateway::addResearchOffer/removeResearchOffer` war bereits vorhanden. Hinweis-Banner im Formular: Acceptance-Flow folgt in Phase 3 nach Entscheidung über Mechanik (Level-Transfer vs. Lizenz). Beide Trade-Seiten haben jetzt einen Tab-Header (Rohstoffe / Forschungen).
- **User-Einstellungen:** `/user/settings` ist nun funktional — Anzeigename und Passwort können geändert werden. Zwei PATCH-Routen (`/user/settings/name`, `/user/settings/password`) mit Validierung und aktuellem-Passwort-Check.
- **Roadmap Phase 3–5:** Phase 3 in 3a (Balancing/Kernmechaniken), 3b (UI/Almanach) und 3c (Onboarding) unterteilt. Phase 4 ("Das Spiel vertiefen": Diplomatie, Rassen, Gilden, Steuern, Berater) und Phase 5 ("Das Spiel erweitern": Außenposten, neue Schiffstypen, galaktische Politik) neu eingeführt. Alle bisher im GDD als "Phase 3" vermerkten Themen in die neue Struktur überführt.

## 2026-04-05 (Spielsystemanalyse, Advisor-UI, Schiffsgeschwindigkeit)

- **Systemanalyse:** Vollständige Bestandsaufnahme aller Module und Spielmechaniken. Roadmap Phase 2 mit konkreten Befunden, Prioritäten und Design-Klarstellungen (1 Kolonie/Spieler, nur PvP-Schiffskämpfe, keine Kolonisierung) aktualisiert. Phase 3 mit vorgemerkten Themen (UI-Umbau, Almanach, Onboarding, Gebäude-Rework) strukturiert. Design-Entscheidung gegen modulare Schiffe dokumentiert.
- **Bugfix `PersonellService::hire`:** `ResourcesService` war nicht im Konstruktor deklariert — hätte bei `dev_mode=false` einen Fatal Error ausgelöst. Zudem Supply-Check jetzt in `DB::transaction()` eingebettet, um Race-Condition bei parallelen Requests zu verhindern.
- **Advisor-UI:** Neue Seite `/advisors` mit AP-Übersicht (Konstruktion/Forschung/Wirtschaft), Beratertabelle (Typ, Rang, AP/Tick, Rang-Fortschritt, Status) und Hire/Fire-Funktion. Ownership-Check beim Entlassen (verhindert, dass fremde Berater entlassen werden können). Nav-Eintrag "Berater" ergänzt.
- **Schiffsgeschwindigkeit:** `moving_speed` für alle 6 Schiffstypen in `config/ships.php` gesetzt (Fighter 4, Fregatte/Small Transporter 3, Battlecruiser/Medium Transporter 2, Large Transporter 1). `FleetService::calcFleetSpeed()` war bereits korrekt implementiert.

## 2026-04-04 (INNN Ereignisse — Polishing)

- **Platzhalter-Fix:** `tech_id` in Event-Parametern wird jetzt gegen Building → Research → Ship aufgelöst (vorher nur Building → "Tech #34" bei Forschungs-/Schiff-Events).
- **Fleet-Name:** Fleet-Events zeigen jetzt den Fleet-Namen statt "Flotte #X".
- **Null-Guards:** `colony_id=0`, `attacker_id=0`, `defender_id=0` zeigen "unbekannt" statt "#0". Alle Platzhalter haben Fallback-Defaults — `:placeholder` kann nie mehr roh erscheinen.
- **Texte:** `techtree_level_down` sagt "Struktur" statt "Gebäude" (gilt auch für Schiff- und Forschungs-Verfall). `galaxy_combat` leicht umformuliert.
- **Layout:** Desktop-Ansicht nutzt max. ~80% Seitenbreite (`col-md-10 col-xl-8`). Area-Badge durch kontextuelles Icon ersetzt.

## 2026-04-04 (Tech-Config-Refactoring)

- **Zentrale Config-Dateien:** `config/buildings.php`, `config/ships.php`, `config/techs.php`, `config/advisors.php` eingeführt als einzige Quelle für alle per-Entity-Mechanik-Werte (Supply-Cost, Moral, Decay-Rate, Credits). Ersetzt die zersplitterten `game.moral.*`- und `game.supply.*`-Sektionen.
- **Decay-Kalibrierung:** Alle Decay-Raten neu kalibiert für 1 Tick = 24 Stunden. Bisherige Werte (1-Tick/Stunde-Auslegung) ergaben 100–400 Tage Verfallszyklen; neue Werte: 7 Tage (Bar/Kasino) bis 60 Tage (Kommandozentrale/Denkmal). Migration `2026_04_04_000001_recalibrate_decay_rates` aktualisiert DB-Tabellen.
- **Lokalisierung:** `lang/de/buildings.php`, `ships.php`, `techs.php`, `advisors.php` mit deutschen Namen und Tooltip-Beschreibungen für alle Spielentitäten.
- **MoralService-Refactoring:** Liest `moral_per_lv`/`moral_per_unit` jetzt aus den neuen Config-Dateien statt aus `game.moral.*`. `GameTick` referenziert Supply-Cap-Werte über `config('buildings.*.supply_cap')`.

## 2026-04-04 (Moralsystem Phase 2)

- **MoralService:** Neuer Service berechnet Kolonial-Moral (-100..+100) aus statischen Faktoren (Gebäude, Forschungen, Schiffe) und One-Shot-Events (`moral_events`-Tabelle). Events desselben Typs im selben Tick stacken nicht — nur der stärkste Wert zählt.
- **Produktionsmultiplikator:** `GameTick::generateResources()` wendet den Moral-Multiplikator an (0.70× Aufruhr .. 1.20× Euphorisch). Schritt 8b berechnet und speichert Moral nach der Ressourcengenerierung.
- **AP-Multiplikator:** `PersonellService::getTotalActionPoints()` skaliert colony-scoped AP mit dem Moral-Multiplikator (0.80× .. 1.10×). Fleet-scoped Navigation-AP bleibt unverändert.
- **Trade-Events:** `TradeGateway::acceptResourceOffer()` feuert `trade_success`-Event für Käufer und Verkäufer. Combat-Events (`combat_won`, `combat_lost`, `colony_attacked`) werden in `GameTick::processCombatOrders()` gefeuert.
- **GDD §13** dokumentiert das vollständige Design inkl. Bänder, Einflussfaktoren, Balance-Entscheidungen (Schiffs-Cap ±30, Event-No-Stacking). `lang/de/moral.php` mit Band- und Event-Bezeichnungen. 53 neue Tests.

## 2026-04-03 (Flottenoperationen Phase 2)

- **Fleet CRUD:** Flotten können jetzt erstellt (POST `/fleet`) und gelöscht (DELETE `/fleet/{id}`) werden. Erstellung erfordert einen verfügbaren Pilot-Advisor (Kommandant) — dieser wird automatisch zugewiesen. Beim Löschen kehrt der Kommandant zur Kolonie zurück.
- **Order-Erstellung:** Spieler können Move-, Trade- und Attack-Orders über das Fleet-Config-UI erteilen (POST `/fleet/{id}/orders`). Move-Orders sind auf intra-System-Bewegung beschränkt (Phase-3-Vorbehalt für interstellare Reisen mit Wurmloch/Sternentor-Mechanik).
- **Multi-Tick-Bewegung:** Flotten bewegen sich tick-basiert mit der Geschwindigkeit des langsamsten Schiffs. `FleetService::addOrder()` legt für jeden Tick des Weges eine Order an.
- **Bug-Fixes:** serialize/json-Mismatch in `_storePathInDb()` behoben (Orders konnten nie verarbeitet werden); `startTick=0`-Fehler in `getPath()`-Aufruf korrigiert; Route-Inkonsistenz zwischen `fleets.js` und `web.php` behoben.
- **Pending Orders** werden in der Fleet-Übersicht angezeigt. GDD §8 und §12 um Bewegungs-Mechanik und Kommandant-Modell (Option A) ergänzt. 17 neue Tests.

## 2026-04-02 (Supply-Cap-Enforcement)

- **Over-Cap-Decay:** Wenn eine Kolonie ihren Supply-Cap überschreitet, verfallen Gebäude und Forschungen mit 2× Decay-Rate. Spieler werden so zum Abbau gedrängt ohne erzwungene Zerstörung.
- **Blockierung:** Neue Level-Ups und Hire-Aktionen bleiben geblockt solange die Kolonie Over-Cap ist (bestehende Logik via `checkRequiredSupplyByEntityId`).
- **`getFreeSupply()`** gibt jetzt negative Werte zurück bei Over-Cap; neue Hilfsmethode `getOverCapColonyIds()` in `ResourcesService`.
- `overcap_factor = 2.0` konfigurierbar in `config/game.php`. 7 neue Tests in `OverCapDecayTest`.

## 2026-04-02 (Economy-AP für Händler)

- **Händler-AP implementiert:** Trade-Aktionen verbrauchen jetzt Economy-AP. Angebot erstellen kostet `max(1, floor(amount × price / 1000))` AP — skaliert mit dem Handelswert. Angebot annehmen kostet 1 AP (Käufer). Angebot entfernen ist kostenlos.
- **Konfigurierbar:** AP-Schwellenwert in `config/game.php` unter `trade.ap_cost_threshold = 1000`.
- **DI:** `PersonellService` per Constructor-Injection in `TradeGateway` eingebunden, explizite Bindung in `AppServiceProvider`.
- 6 neue Tests in `TradeApTest` (Skalierung, Locks, Exceptions, dev_mode-Bypass).

## 2026-04-02 (Trade Acceptance-Flow)

- **Angebote annehmen:** Spieler können Ressourcen-Angebote anderer Spieler annehmen. Instant Transfer — Ressourcen und Credits wechseln sofort den Besitzer, kein physischer Transport. Komplettkauf only, Angebot wird nach Abschluss gelöscht.
- **Restriction durchgesetzt:** `restriction`-Feld wird server-seitig geprüft (0=alle, 2=gleiche Fraktion, 3=gleiche Rasse). Wert 1 (Gruppe) wird bis zur Implementierung des Gruppenmoduls wie 0 behandelt. Annehmen-Button im UI je nach Restriction ein-/ausgeblendet.
- **Sicherheit:** Selbstkauf blockiert, Buyer-IDs aus Session (kein POST-Injection), alle Transfers atomar in `DB::transaction()`. 28 neue Tests inkl. Edge Cases und Rollback-Konsistenz.
- **GDD §11** mit Acceptance-Flow und restriction-Semantik aktualisiert.

## 2026-04-02 (Laminas/Zend-Reste entfernt)

- **Komplettbereinigung:** `module/` (11 Module, ~200 PHP-Dateien), `config/autoload/` (LmcUser, ZfcRbac, global, local), `init_autoloader.php` und `test/Bootstrap.php` gelöscht — der gesamte alte Laminas-Modulbaum ist damit aus dem Repo entfernt.
- **Kommentarbereinigung:** Laminas-Migrations-Annotationen aus 19 aktiven Laravel-Dateien entfernt.
- **README aktualisiert:** Laminas → Laravel 12, PHP 8.0 → 8.2+, `phpunit` → `php artisan test`, Dev-Server auf `artisan serve`.

## 2026-04-02 (Onboarding-Risiken behoben)

- **Race Condition:** `OnboardingService::setupNewPlayer()` läuft jetzt in `DB::transaction()` — SQLite serialisiert Writes, sodass zwei simultane Registrierungen nicht denselben Planeten belegen können.
- **spot=1 hardcoded:** `ColonyService::createColony()` berechnet den Spot jetzt dynamisch (`MAX(spot) + 1`), sodass mehrere Kolonien auf demselben Planeten korrekt auf verschiedene Spots verteilt werden.
- **Kein freier Planet:** `RegisterController::register()` wickelt User-Erstellung und Onboarding atomar in einer Transaktion ab — bei vollem Universum wird kein verwaister Account angelegt, sondern eine Fehlermeldung im Registrierungsformular angezeigt.
- **UNIQUE-Constraint** auf `(system_object_id, spot)` in `glx_colonies` als Datenbank-Safety-Net hinzugefügt.
- **LoginTest:** `TestSeeder` in `setUp()` ergänzt. Neuer Test `test_registration_fails_when_no_free_planets`.

## 2026-04-01 (Onboarding nach Registrierung)

- **OnboardingService:** Neuer Service `setupNewPlayer()` — sucht freien Planeten, erstellt Kolonie, setzt Startressourcen (3000 Cr, 15 Supply, 500/500/500/100/100/100 Kolonieressourcen) und platziert CommandCenter auf Level 1.
- **RegisterController:** Ruft nach Login den Onboarding-Service auf und setzt `activeIds.colonyId` in der Session.
- **LoginController:** Triggert Onboarding beim Login wenn User noch keine Kolonie hat (Legacy-Accounts).
- **ColonyService:** Neue Methode `createColony()` für programmatische Kolonie-Erstellung.
- 3 neue Tests in `OnboardingTest`.

## 2026-04-01 (Tests E1/E2/I4)

- **Tests E1/E2 (AP-Delta-Locking):** `invest('add')` lockt korrekt die investierten AP (E1); AP-Locks sind tick-scoped und verfallen nach Tick-Advance (E2). In `PersonellServiceTest`.
- **Test I4 (Cross-Colony-Exploit):** Neuer `TechtreeControllerTest` — verifiziert dass Controller-Aktionen immer nur die eigene Kolonie (session-basiert) betreffen und keine `colony_id` per URL injiziert werden kann.

## 2026-03-31 (GDD Tick-Tabelle, Supply Enforcement, Rang-Schwellen in Config)

- **Rang-Schwellen in config ausgelagert:** `RANK_UP_THRESHOLDS` (PersonellService) und `AP_BY_RANK` (Advisor) durch `config('game.advisor.rank_thresholds')` und `config('game.advisor.ap_per_rank')` ersetzt. Widerspruch zwischen GDD (Rang 3 bei 20 Ticks) und Code (30 Ticks) aufgelöst — config jetzt auf 20. Test entsprechend angepasst.

## 2026-03-31 (GDD Tick-Tabelle, Supply Enforcement)

- **GDD §2 Tick-Tabelle:** Von 6 auf 9 Schritte aktualisiert — Ship Decay (5), Research Decay (6), Supply Cap (7, statt "Supply Generation"), Advisor Ticks (9) ergänzt. Beschreibungen präzisiert. Widersprüchlichen "Konsequenz für den Tick"-Abschnitt durch korrekten Text zum Cap-Modell ersetzt.

## 2026-03-31 (Supply Enforcement)

- **Supply Enforcement:** Beim Level-Up von Gebäuden, Schiffen und Forschungen wird jetzt geprüft, ob genügend freies Supply (Cap − aktuell genutztes Supply) vorhanden ist. Neue Methode `ResourcesService::getFreeSupply()` berechnet freies Supply aus Cap und Summe aller Entity-Supply-Kosten. `AbstractTechnologyService::checkRequiredSupplyByEntityId()` blockiert Level-Ups wenn Kapazität fehlt. `PersonellService::hire()` prüft supply_cost pro Berater (aus `config/game.php`). Alle Checks werden im dev_mode bypassed. 2 neue Tests in `BuildingServiceTest`.

## 2026-03-30 (Agenten aktualisiert, Ressourcenleiste)

- **Agenten-Updates:** `backend-coder` und `ui-specialist` auf Laravel/Blade aktualisiert; `project-manager` auf Phase 2/3-Stand gebracht; neuer `content-writer`-Agent für Lore, Beschreibungen und INNN-Texte; README.md aktualisiert.
- **Ressourcenleiste:** Credits (ID 1) und Supply (ID 2) immer an erster Stelle und visuell hervorgehoben (größer, dickerer Rand, Box-Shadow). Optischer Trenner zwischen primären und sekundären Ressourcen.

## 2026-03-30 (colonyShip entfernt)

- **colonyShip (id=88) vollständig entfernt:** Migration löscht ship aus DB; testdata.sqlite.sql bereinigt; MasterDataSeeder, GDD, CLAUDE.md, lang-Dateien aktualisiert. `colonize`-Order-Typ aus `config/game.php` entfernt.
- Testreferenz angepasst: FleetServiceTest erwartet jetzt 4 statt 5 Schiffe in Fleet 10.

## 2026-03-30 (Supply Cap und Decay im GameTick implementiert)

- **Supply: Cap-Modell implementiert** — `calculateSupply()` setzt `user_resources.supply` jetzt als Kapazitäts-Cap (SET statt INCREMENT): `cap = CC_flat (15) + housing_level × 8`, max 200. Ohne CommandCenter → Supply = 0.
- **Decay: per-Entity-Werte** — `processDecay()` aufgeteilt in `processBuildingDecay()`, `processShipDecay()` und `processResearchDecay()`. Alle drei nutzen die individuellen `decay_rate`-Werte aus den Stammdaten-Tabellen statt dem globalen Fallback-Wert. Decay ist fraktional (REAL).
- **Schiff-Decay** — Schiffe in Kampf-Ticks erhalten Faktor 2 (`combat_factor`). Bei SP ≤ 0 wird der `fleet_ships`-Eintrag gelöscht (kein Level-Down, Schiff vernichtet). Fix: `DB::table()` statt Eloquent-Update bei Composite-Key-Tabellen.
- **10 neue Tests** — Supply-Cap (CC-Pflicht, Housing-Skalierung, Max-Cap), Building-Decay (fraktional, Level-Down, Level-0-Skip), Ship-Decay (fraktional, Vernichtung), Research-Decay (fraktional, Level-Down).

## 2026-03-29 (Decay- und Supply-Migrationen)

- **Zwei neue Migrations:** `decay_rate REAL` und `supply_cost INTEGER` zu `buildings`, `ships`, `researches` hinzugefügt; `status_points REAL DEFAULT 20` zu `fleet_ships` (neu — Schiffe hatten bislang kein Status-Tracking).
- **Original-Migrationen angepasst:** `colony_buildings` und `colony_researches` verwenden jetzt `double` für `status_points` (Voraussetzung für fraktionale Decay-Werte).
- **MasterDataSeeder:** Befüllt alle neuen Felder mit den im GDD §6/§7 beschlossenen Werten (decay_rate 0.05–0.20, supply_cost 0–30). Wird automatisch vom TestSeeder aufgerufen.
- **testdata.sqlite.sql aktualisiert:** Positionale INSERT-Statements um neue Spalten ergänzt (NULL-Platzhalter für Stammdaten, 20.0 für fleet_ships).

## 2026-03-28 (Trade-Modul repariert)

- **Vier kritische Bugs behoben:** `withoutLayout()` existiert nicht in Laravel → durch Redirect+Flash ersetzt; Filter funktioniert jetzt per GET; Remove-Formular sendete falsche Felder (`offer_id`/`offer_type` statt Composite-Key); Create-Modal hatte kein `colony_id`-Feld.
- **Validierung verbessert:** `amount`/`price` auf `min:1` angehoben (konsistent mit UI), `removeOffer`-Endpunkt validiert jetzt `colony_id` und `direction`, strict equality beim `user_id`-Vergleich in den Views.
- **25 neue Tests:** HTTP-Controller-Tests für alle Trade-Endpunkte (GET-Filter, POST-Erstellen, POST-Löschen, Authentifizierung, Ownership-Checks, Upsert-Pfad).
- **Offene Design-Fragen identifiziert:** Forschungshandel-Semantik (sinkt Level beim Verkauf?) und `restriction`-Feld (Bedeutung ungeklärt) — werden in der nächsten Session geklärt bevor der Acceptance-Flow implementiert wird.

## 2026-03-28 (Berater-System: advisors-Tabelle, Rang-System, Kommandant)

- **Neue `advisors`-Tabelle:** Berater sind jetzt individuelle Einträge (id, user_id, rank, active_ticks) statt level-aggregierte Zeilen in colony_personell. Bestehende Daten aus colony_personell und fleet_personell wurden migriert.
- **Rang-System implementiert:** Junior(1)=4 AP, Senior(2)=7 AP, Experte(3)=12 AP/Tick. Automatischer Rang-Aufstieg nach 10 bzw. 30 aktiven Ticks via GameTick.
- **Kommandant fleet-assignable:** `assignToFleet()` / `unassignFromFleet()` — nur Kommandant-Typ erlaubt (personell.can_command_fleet=true). Prüfung auf DB-Ebene per Flag, Durchsetzung im Service.
- **Arbeitslos-Zustand:** `fire()` löscht keine Berater mehr, setzt nur colony_id/fleet_id auf NULL. Vorbereitung für Berater-Handel zwischen Spielern (Phase 3).
- **Passagier-Zustand:** fleet_id gesetzt + is_commander=false = Berater als Passagier auf Flotte (alle Typen erlaubt).
- **PersonellService** komplett neu geschrieben auf advisors-Tabelle. `hire()` gibt Advisor-Instanz zurück.
- **GDD Abschnitt 12** mit vollständigem Datenmodell und Zustandstabelle aktualisiert.

## 2026-03-27 (AP-System: Berater und Flottenkommandant)

- **AP-System vervollständigt:** Alle vier Berater-Typen (Ingenieur, Wissenschaftler, Pilot/Kommandant, Händler) vollständig implementiert. Navigation-AP sind jetzt fleet-scoped statt colony-scoped — der Kommandant fliegt mit der Flotte.
- **DB-Migration `locked_actionpoints`:** Schema von `(tick, colony_id, personell_id)` auf `(tick, scope_type, scope_id, personell_id)` umgestellt. `scope_type='colony'` für Bau/Forschung/Wirtschaft, `scope_type='fleet'` für Navigation.
- **FleetService:** AP-Kosten-Check bei `addOrder()` integriert. Konfigurierbar in `config/game.php → fleet.order_costs`. Im Dev-Mode übersprungen.
- **GDD Abschnitt 12:** Berater & Aktionspunkte dokumentiert (alle 4 Typen, Formel, Scope, Implementierung).
- **GDD Abschnitt 1.1:** Neues Kapitel "Designprinzipien" — militärische Aktionen kosten immer mehr AP als zivile (Kernprinzip für das gesamte Spiel inkl. Verträge, Diplomatie).
- **Offenes Designthema:** Das Berater-System (Berater als Gebäude mit Leveln) muss grundsätzlich überarbeitet werden — wird in einer eigenen Session angegangen.

## 2026-03-26 (GDD erstellt)

- **Game Design Document:** `docs/GDD.md` neu angelegt. Dokumentiert alle bisher implementierten Spielmechaniken: Tick-System (Zeitberechnung, Berechnungsfenster, Schrittreihenfolge), Ressourcenproduktion, Supply-Generierung, Gebäude-Verfall, Flottenorders (Move/Trade), Kampfsystem. Alle Balancewerte mit Verweis auf `config/game.php`.

## 2026-03-26 (Phase 2: Tick-System, Teil 2)

- **Gebäude-Verfall:** Jeder Tick dekrementiert `status_points` um 1 pro Kolonie-Gebäude. Erreicht `status_points` 0, verliert das Gebäude ein Level und `status_points` wird auf `max_status_points` zurückgesetzt. INNN-Event `techtree.level_down` wird erzeugt. Rate konfigurierbar in `config/game.php → decay.rate`.
- **Supply-Generierung:** Jeder Tick addiert Supply zu jedem User: `Σ(CommandCenter.Level × 5) + Σ(HousingComplex.Level × 10)` über alle Kolonien des Users. Rates konfigurierbar in `config/game.php → supply`.
- **Kampfsystem (einfach):** Attack-Orders werden verarbeitet: Angreifer bewegt sich zu den Zielkoordinaten, gegnerische Flotten werden gesucht. Kampfstärke = `Σ(Schiffanzahl × Kampfwert)`. Verluste werden proportional zur gegnerischen Stärke berechnet (nicht-Kampfschiffe bleiben verschont). INNN-Events für beide Seiten. Kampfwerte konfigurierbar in `config/game.php → combat.ship_power`.
- **lang/de/events.php:** Key `events.techtree_level_down` ergänzt für INNN-Anzeige.

## 2026-03-26 (Phase 2: Tick-System)

- **Tick-Processor:** `php artisan game:tick [--tick=N]` implementiert. Der Command verarbeitet für den angegebenen Tick: (1) Fleet-Move-Orders — Flotte wird auf die befohlenen Koordinaten gesetzt, `was_processed=1`; (2) Fleet-Trade-Orders — Ressourcentransfer zwischen Kolonie und Flotte (Kauf/Verkauf), `colony_id` als Schlüssel; (3) Ressourcengenerierung — alle Kolonien erhalten pro Industrie-Gebäude `level × rate` Ressourcen pro Tick (konfigurierbar in `config/game.php` unter `production`). Für jede verarbeitete Move- und Trade-Order wird ein INNN-Event erzeugt.
- **config/game.php:** Produktionsraten ergänzt (`oremine→ferum: 10/Level`, `silicatemine→silicates: 10/Level`, `waterextractor→water: 10/Level`). Scheduling-Stub für `dailyAt('03:00')` als Kommentar hinterlegt.
- **Diagnose:** Die in der DB vorhandene Trade-Order hatte einen JSON-Datenfehler (duplizierter `"colony"`-Key statt `"colony_id"`). Kein Designfehler — `trade.js` nutzt korrekt `colony_id`. Bestehende Test-Daten sind als `was_processed=1` markiert.

## 2026-03-24 (UI-Aufwertung & Bugfixes)

- **Techtree:** Grid-Dimensionen korrigiert (war fälschlicherweise 6×16 statt 16×6). Leader Line ersetzt das manuelle SVG-Drawing — Abhängigkeitspfeile werden jetzt sauber mittig auf den Buttons gesetzt. Toggle-Buttons (Gebäude/Forschungen/Schiffe/Berater) wieder eingebaut, Toggles steuern auch Leader-Line-Instanzen.
- **Techtree-Buttons:** Modernes Flat-Design mit farbigem linken Akzentrand (lila/grün/gelb/grau je Typ) statt alter Farbverläufe. `notexists`-Buttons in ausgewaschener Variante.
- **Galaxy:** `galaxy.js` komplett auf Leaflet umgestellt — liest Systemkoordinaten aus `data-x`/`data-y`-Attributen statt inline PHP. Neue Routen `/galaxy/{sid}` und `/galaxy/json/getmapdata/{x}/{y}` ergänzt. `TechtreeController` nutzt `resolveColonyId()` als Session-Fallback über `ColonyService`.
- **Resource Bar:** Heller Hintergrund (`#f8f9fa`), fixiert unterhalb der Navbar. Ressourcen als farbige Chips mit Akzentfarbe je Ressourcentyp.

## 2026-03-24 (Blade-Templates)

- **Layout:** `fixed-top` zur Navbar-Klasse ergänzt, damit der Content nicht hinter der Leiste verschwindet.
- **Techtree-Index:** Vollständig auf das originale Grid-Layout portiert — 16×6 Zellen-Raster, `.techdata`-Spans mit `id="techsource-{row}-{col}"`, die techtree.js per `init()` in die Grid-Zellen verschiebt. Requirement-Linien-Daten als `.requirementsdata`-Spans eingebettet. Pro Tech ein leeres `.techModal`-Shell, das per AJAX befüllt wird.
- **Techtree-Technology-Partial:** Neues AJAX-Partial (kein `@extends`) mit vollständiger Modal-Dialog-Struktur: Kosten/Voraussetzungen-Tabelle, `techstatus_bar` und `techlevelup_bar` Partials, Levelup/Leveldown-Buttons mit korrekten IDs im Format `{type}-{id}|{order}` für techtree.js. Sonderbehandlung für Personell (Anheuern/Feuern statt Ausbauen/Abbauen) und Ships (zusätzliche Forschungs-Voraussetzung).
- **Techtree-Partials:** `techstatus_bar.blade.php` und `techlevelup_bar.blade.php` in `resources/views/techtree/partials/` angelegt — segmentierte Bootstrap-Progress-Bars mit klickbaren `<a>`-Segmenten für AP-Investment und Reparatur.
- **TechtreeController:** Neues `action()`-GET-Endpoint (`/techtree/{type}/{id}/{order}[/{ap}]`) für das techtree.js-AJAX-Muster, das nach jedem Klick die gesamte Modal-Partial neu lädt. `$buildings` und `$researches` werden jetzt an `technology()` übergeben.
- **Fleet-Index:** Auf Laminas-Struktur portiert — eigene Flotten links, fremde Flotten rechts, Formular zum Anlegen neuer Flotten im `<tfoot>`, Lösch-Button mit Bestätigungs-Dialog.
- **Fleet-Config:** Vollständig auf die Laminas-Vorlage portiert — Kolonie-Inventar-Tabellen für Schiffe/Personal/Forschungen/Ressourcen mit AJAX-Placeholdern (`…`), Menge-Auswahl-Buttons, Transfer-Buttons. Alle CSS-Klassen für fleets.js kompatibel (`fc-item`, `fc-mid`, `data-type/id/cargo`, `#fleet_id`, `#colony_id`).
- **Trade-Resources/Researches:** Rohstoff-Anzeige mit Icon und Tooltip statt roher ID. "Angebot erstellen"-Button mit Modal-Formular. Eigene Angebote bekommen Lösch-Button. Filter-Formular auf GET umgestellt.
- **Messages-Outbox:** Deaktivierte Aktionsbuttons (Thumbs-Up/Down, Antworten) im Accordion-Body ergänzt.

## 2026-03-24 (Schritt 11+12)

- **Layout & Navigation (Schritt 11):** `resources/views/layouts/app.blade.php` vollständig überarbeitet — Bootstrap-Navbar mit allen migrierten Modulen (Galaxis, Flotte, Techtree, Handel, Nachrichten), Ressourcenleiste via View Composer (AppServiceProvider::boot registriert den Composer, injiziert `$resourceBarPossessions` aus `ResourcesService::getPossessionsByColonyId()` in den Layout-View), Spiel-JS-Dateien (nouron.js, techtree.js, fleets.js, trade.js, innn.js), Tooltip-Init, Sub-Nav-Slot via `@hasSection('subnav')`. Fehler-Seiten `errors/404.blade.php` und `errors/500.blade.php` erstellt.
- **Cleanup (Schritt 12):** Alle 24 Laminas-Pakete sowie `lm-commons/lmc-user` und `firephp/firephp-core` aus `composer.json` entfernt. Laminas-Module-Autoload-Einträge (`Application`, `Core`, `Colony`, `Fleet`, `Galaxy`, `INNN`, `Map`, `Resources`, `Techtree`, `Trade`, `User`) aus `autoload` und `autoload-dev` bereinigt. `laminas/laminas-test` aus `require-dev` entfernt. Laminas-Testsuiten aus `phpunit.xml` entfernt, Bootstrap auf `vendor/autoload.php` umgestellt. `composer update` ausgeführt — 81 Pakete (statt 118 vorher). 187/187 Laravel Feature-Tests weiterhin grün.



## 2026-03-24 (Techtree)

- **Techtree-Modul migriert (Schritt 10):** 10 Eloquent-Modelle erstellt (`Building`, `BuildingCost`, `ColonyBuilding`, `LockedActionpoint`, `Personell`, `PersonellCost`, `Research`, `ResearchCost`, `Ship`, `ShipCost`). `AbstractTechnologyService` als gemeinsame Basis für alle Techtree-Services implementiert (Prerequisite-Checks, AP-Investment, levelup/leveldown, Kostenzahlung). Konkrete Services `BuildingService`, `ResearchService`, `ShipService`, `PersonellService` (inkl. AP-Verwaltung, lockActionPoints, hire/fire) und `TechtreeColonyService` (Gesamtübersicht mit Merge aus Master- und Kolonie-Tabellen). `TechtreeController` mit 3 Routen unter `/techtree` (index, technology-Detail-Popup, order). Blade-Views `techtree/index.blade.php` und `techtree/technology.blade.php`. Services in AppServiceProvider registriert. 22 neue Feature-Tests, Gesamtstand: 185 Tests grün.

## 2026-03-23 (Fleet)

- **Fleet-Modul migriert (Schritt 9):** Eloquent-Modelle `Fleet`, `FleetShip`, `FleetResearch`, `FleetPersonell`, `FleetOrder`, `FleetResource` sowie `ColonyShip`, `ColonyResearch`, `ColonyPersonell` (für transferTechnology). `App\Services\FleetService` portiert alle Methoden: getFleet, saveFleet, saveFleetOrder, getFleetOrdersByFleetIds, transferShip/Research/Personell/Technology/Resource, getFleetShip/Research/Personell/Resource (Singular + plural), getOrders, getFleetsByUserId/EntityId/Coords, getFleetTechnologies. `App\Http\Controllers\Fleet\FleetController` mit 5 Routen unter `/fleet` (index, config, addtofleet, technologies, resources). Blade-Views `fleet/index.blade.php` und `fleet/config.blade.php`. `Colony::getCoords()` ergänzt (wurde für transferTechnology benötigt). 23 Feature-Tests grün (2 skipped: addOrder/transferResource wie im Original), Gesamtstand: 163 Tests grün.

## 2026-03-23 (Trade)

- **Trade-Modul migriert (Schritt 8):** Eloquent-Modelle `TradeResource`, `TradeResearch` (Basistabellen, composite PK, kein incrementing) sowie `TradeResourceView`, `TradeResearchView` (lesen aus `v_trade_resources` und `v_trade_researches`). `App\Services\TradeGateway` portiert alle Operationen: getResources, getResearches, addResourceOffer, addResearchOffer, removeResourceOffer, removeResearchOffer — mit Ownership-Check via ColonyService. `App\Http\Controllers\Trade\TradeController` mit 5 Routen unter `/trade`. Blade-Views unter `resources/views/trade/`. Service in AppServiceProvider registriert. 18 neue Feature-Tests, Gesamtstand: 140 Tests grün.

## 2026-03-23

- **INNN-Modul migriert (Schritt 7):** `InnnMessage`, `InnnMessageView`, `InnnEvent`, `InnnNews` (Eloquent). `MessageService` (getMessage, getInboxMessages, getOutboxMessages, getArchivedMessages, sendMessage, setMessageStatus) und `EventService` (getEvent, getEvents, createEvent). `MessageController` mit 8 Routen unter `/messages`. 4 Blade-Templates (inbox, outbox, archive, compose). 39 neue Feature-Tests, 122/122 grün.

## 2026-03-21

- **Code-Analyse:** Vollständige Analyse der Laminas-Codebasis durchgeführt (373 PHP-Dateien, 11 Module, 42 Controller, 35 Table-Klassen, 98 Tests). Migrationsoptionen Laravel, PHP-Microframework und Python/Flask gegenübergestellt — Laravel als empfohlener Migrationspfad identifiziert.
- **Branch-Management:** Branch `claude/analyze-test-coverage-3rtlh` in `laminas-migration` gemergt (Fast-Forward). Branch lokal und remote gelöscht.
- **Tagging:** Tag `laminas-migration-finished` auf den aktuellen Stand von `laminas-migration` gesetzt. Tag `legacy-zf2-final` auf den letzten ZF2-Commit (`b325183`) gesetzt, um das Ende der Legacy-Version zu kennzeichnen.
- **README aktualisiert:** Quickstart auf PHP 8 / `composer install` / `./vendor/bin/phpunit` aktualisiert, Zend Framework 2 durch Laminas ersetzt, Google+ entfernt, Copyright-Jahr auf 2026 aktualisiert.
- **Pull Request erstellt:** PR von `laminas-migration` → `master` eröffnet, der die vollständige ZF2→Laminas+Bootstrap5-Migration zusammenfasst.
- **INNN Bugfix:** Schema-Inkonsistenz behoben — `nouron.db` verwendete camelCase-Spalten (`isRead`, `isArchived`, `isDeleted`) statt snake_case wie in `schema.sqlite.sql` und `test.db`. Tabelle und View in `nouron.db` neu erstellt, `MessageService.php` bleibt bei snake_case. Alle 19 INNN-Tests grün.
- **Testdaten:** Reiche Testdaten in `nouron.db` eingefügt: 3 neue Kolonien (Homer, Marge, Bart 2nd), 3 neue Flotten, 19 neue Nachrichten, 14 Events, 4 News-Einträge, 15 Handelsrouten für Ressourcen und Forschungen.
- **PR gemergt:** `laminas-migration` → `master` gemergt (Merge-Commit `7f3cac3`). Tag `laminas-migration-finished` auf den Merge-Commit aktualisiert.

## 2026-03-23 (INNN)

- **INNN-Modul migriert (Schritt 7):** Eloquent-Modelle `InnnMessage`, `InnnMessageView` (liest aus `v_innn_messages`-View mit Sender/Empfanger-Namen), `InnnEvent`, `InnnNews`. `App\Services\MessageService` portiert alle Methoden: getMessage, getInboxMessages, getOutboxMessages, getArchivedMessages, sendMessage, setMessageStatus. `App\Services\EventService` mit getEvent, getEvents, createEvent. `App\Http\Controllers\INNN\MessageController` vereint Inbox, Outbox, Archiv, Compose, Send, React, Remove. Blade-Views unter `resources/views/messages/`. Routen unter `/messages` (auth-geschützt). Services in AppServiceProvider registriert. 39 neue Feature-Tests, Gesamtstand: 122 Tests grün.

## 2026-03-23 (Galaxy)

- **Galaxy-Modul migriert (Schritt 6):** `App\Models\GlxSystem` (liest aus `v_glx_systems`-View), `App\Models\GlxSystemObject` (liest aus `v_glx_system_objects`-View). `App\Services\GalaxyService` portiert alle Methoden aus `Galaxy\Service\Gateway`: getSystems, getSystem, getSystemObjects, getSystemObject, getSystemObjectByColonyId, getSystemObjectByCoords, getObjectsByCoords, getColoniesByCoords, getSystemBySystemObject, getSystemByObjectCoords, getDistance, getDistanceTicks, getPath (Bresenham mit Speed). `GalaxyController` vereint IndexController, SystemController und JsonController (index, showSystem, getMapData). Blade-Views `galaxy/index.blade.php` und `galaxy/system.blade.php`. Galaxy-Routen unter `/galaxy` (auth-geschützt). Config-Werte `galaxy_view` und `system_view` in `config/game.php`. 36 neue Feature-Tests grün, Gesamtstand: 83 Tests grün.

## 2026-03-23

- **Resources-Modul migriert (Schritt 5):** `App\Models\Resource`, `ColonyResource`, `UserResource` (Eloquent). `App\Services\ResourcesService` mit allen Methoden (getResources, getColonyResources, getUserResources, getPossessionsByColonyId, check, payCosts, increaseAmount, decreaseAmount). `JsonController` mit 3 Endpunkten (GET /resources, /resources/colony/{id}, /resources/resourcebar). Blade-Partial für Ressourceleiste. 15 Feature-Tests grün. Bugfix: Composite-PK-Problem bei ColonyResource-Updates gelöst via `DB::table('colony_resources')->updateOrInsert(...)` statt Eloquent-`save()`.
- **Colony-Modul migriert (Schritt 4):** `App\Models\Colony` (Eloquent, liest aus `v_glx_colonies`-View), `App\Services\ColonyService` (alle 8 Methoden aus Laminas-Port: getColonies, getColony, getColoniesByUserId, checkColonyOwner, getPrimeColony, setActiveColony, setSelectedColony, getColoniesByCoords, getColonyByCoords, getColoniesBySystemObjectId). 24 Feature-Tests grün.
- **Test-Infrastruktur verbessert:** `DB_FOREIGN_KEYS=false` in `.env.testing` — SQLite lässt `PRAGMA foreign_keys = OFF` innerhalb von Transaktionen nicht zu, daher wird FK-Enforcement global für Tests deaktiviert. Die Testdaten aus `testdata.sqlite.sql` sind bereits konsistent. Colony-Tests seeden via `$this->app->make(TestSeeder::class)->run()` in `setUp()` innerhalb der offenen Test-Transaktion.
- **User/Auth-Modul migriert (Schritt 3):** `App\Models\User` (Eloquent, `user_id` PK, bcrypt-kompatibel), `LoginController`/`RegisterController`/`UserController`, Blade-Views für Login/Register/User-Profil, `routes/web.php` mit Guest- und Auth-Routen, angepasste `UserFactory`.
- **Test-Infrastruktur komplett:** `TestSeeder` spielt die Simpsons-Testdaten aus `data/sql/testdata.sqlite.sql` in die `:memory:`-DB ein — Laravel Feature Tests nutzen dieselbe kanonische Testbasis wie die Laminas Unit Tests. `DatabaseSeeder` ruft `TestSeeder` auf. 8/8 Laravel Feature Tests grün.

## 2026-03-22

- **Agenten-Definitionen angereichert:** Alle 7 `.claude/agents/`-Definitionen mit projektspezifischem Wissen ergänzt (Testpfade, PHPUnit-Binary, Factory-Pattern, Base-Classes, SQLite-Limitierungen, JS-Module, Nouron-2026-Vision).
- **Phase 1b definiert:** Laminas → Laravel als neue Phase 1b in CLAUDE.md und ROADMAP.md aufgenommen. ROADMAP.md mit 12-stufigem Migrationsplan erstellt (Bestandsaufnahme: 373 PHP-Dateien, 94 Factories, 31 Tables, 108 Tests).
- **Laravel 12 aufgesetzt (Schritt 0):** `laravel/framework ^12.0` neben Laminas installiert, PHPUnit 9.5 → 11.5 angehoben, `laminas/laminas-log` wegen psr/log-Konflikt entfernt, `AbstractService` auf Noop-Logger umgestellt. Verzeichnisstruktur (app/, bootstrap/, routes/, database/, storage/) und Entry Point eingerichtet.
- **DB-Migrations erstellt (Schritt 1):** 35 Laravel-Migration-Dateien + 6 Views aus `schema.sqlite.sql` übersetzt, korrekte FK-Reihenfolge, `colony_buildings` FK-Fehler korrigiert, `MIGRATION_LOG.md` erstellt.
- **Core-Schicht implementiert (Schritt 2):** `TickService`, `ValidatesId`-Trait, `BaseController` als Laravel-Äquivalente für `Core\Service\Tick`, `AbstractService._validateId()` und `IngameController`. `config/game.php` für Spielkonfiguration angelegt.
