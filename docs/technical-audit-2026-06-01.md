# Technischer Audit — Nouron (Stand: 01.06.2026)

Umfang: PHP-Backend, Frontend/UI, Spielmechaniken, Datenbank, Sicherheit.  
Methode: Statische Analyse aller Controller, Services, Models, Blade-Templates, JS-Dateien, Config und Migrations.

---

## KRITISCH — Sofort beheben (blockiert Funktionalität oder ist sicherheitsrelevant)

### [K1] `serialize()` → JSON-Injection / RCE-Risiko
**Dateien:** `app/Services/RunProgressService.php:681`, `app/Services/EventService.php:62`, `app/Console/Commands/GameTick.php` (mind. 11 Stellen: Z. 229, 277, 375, 410, 433, 544, 578, 618, 675, 839, 979)  
PHP `serialize()` in Datenbankfeldern ist ein Object-Injection-Vektor. Das `@unserialize()` in `resources/views/messages/events.blade.php:15` liest diese Daten unkontrolliert zurück.  
**Fix:** Überall `serialize()` → `json_encode()`, `unserialize()` → `json_decode()`.

---

### [K2] jQuery noch aktiv — Core-Features komplett broken
**Dateien:** `public/js/techtree.js` (gesamte Datei, 210 Zeilen in `$(document).ready()`), `public/js/user.js` (Z. 10, 30, 61 — `$.post()`, `$.ajax()`)  
jQuery wurde entfernt, aber diese Dateien referenzieren es noch vollständig. Betroffen:
- **Tech-Tree:** Detailmodal lädt nicht, Klick-Handler funktionieren nicht.
- **Diplomatie/Kontakte:** Komplettes Feature broken (`.addDiplomat`, `.removeContact`).

**Fix:** Beide Dateien auf native `fetch()` + Alpine.js umschreiben.

---

### [K3] Supply-Cap-Bug bei mehreren Wohnhabitat-Instanzen
**Datei:** `app/Console/Commands/GameTick.php:727–730`  
```php
->where('building_id', 28)->value('level');  // Nimmt nur die ERSTE Instanz
```
Wohnhabitat ist ein instanziertes Gebäude (`is_instanced=1`). Mit 3 Instanzen à Level 2 wird Supply-Cap falsch berechnet: `26 statt 58`. Das macht das Spiel systematisch schwerer als designt.  
**Fix:** `.value('level')` → `.sum('level')`.

---

### [K4] Fehlende Colony-Ownership-Prüfung in mehreren Controllern
**Dateien:** `app/Http/Controllers/Colony/ColonyController.php:101–119` (`exploreTile`, `deepScanTile`), `app/Http/Controllers/Trade/TradeController.php:68–80`  
Ein authentifizierter User kann `colony_id` eines fremden Spielers übergeben — keine Prüfung ob die Colony ihm gehört.  
**Fix:** Vor jeder Colony-Operation `checkColonyOwner($colonyId, Auth::id())` prüfen, sonst `abort(403)`.

---

### [K5] Mass Assignment: `user_id` in `Fleet::$fillable`
**Datei:** `app/Models/Fleet.php:12`  
`user_id` ist in `$fillable`. Ein Angreifer könnte per präpariertem Form-Post eine Fleet einem anderen User zuordnen.  
**Fix:** `user_id` aus `$fillable` entfernen; im Controller explizit setzen.

---

### [K6] XSS-Vulnerabilität in `building-detail.blade.php`
**Datei:** `resources/views/partials/building-detail.blade.php:26, 30–31, 37, 40`  
```blade
x-show="{!! $expr !!}.image_slug"
:src="'/img/buildings/' + {!! $expr !!}.image_slug + '.webp'"
```
`$expr` wird unescaped in Alpine.js-Attributen ausgegeben.  
**Fix:** `{!! $expr !!}` → `{{ json_encode($expr) }}` (bzw. `@json($expr)`).

---

### [K7] Fehlende DB-Transaktionen in Ressourcentransfers
**Datei:** `app/Services/BarService.php:85–115`  
`decreaseAmount()` und `increaseAmount()` werden ohne `DB::transaction()` nacheinander aufgerufen. Bei Absturz nach dem ersten Call sind Ressourcen weg ohne Gegenwert.  
**Fix:** Beide Calls in `DB::transaction(function() { ... })` wrappen. Gleiches Muster in `MerchantService` prüfen.

---

### [K8] AP-Items vom Händler haben keinen Effekt
**Datei:** `app/Services/MerchantService.php:24, 283`  
```php
// TODO Phase 4: integrate with PersonellService to credit AP directly.
```
Kauf eines `ap_flex`/`ap_targeted`-Items bucht Credits ab, aber der AP-Bonus wird nicht gutgeschrieben. Spieler zahlen für nichts.  
**Fix:** `PersonellService` um `creditAp(colonyId, apType, amount)` erweitern und im `MerchantService::purchase()` aufrufen.

---

### [K9] Knowledge-CC-Gate dokumentiert aber nicht implementiert
**Datei:** `config/game.php:213–216`  
```php
// Enforcement logic not yet implemented — this entry documents the design rule...
```
Spieler können Knowledge Level 4/5 ohne CC Level 4/5 freischalten. Core-Progression-Gate fehlt.  
**Fix:** Guard in `ResearchService::invest()` / `levelup()` einbauen, der `config('game.knowledge_cc_level_cap')` ausliest und bei Verstoß abbricht.

---

## HOCH — Diese Woche beheben

### [H1] JSON in `data-`-Attributen durch `{{ }}` doppelt escaped
**Dateien:** `resources/views/galaxy/system.blade.php:75–77`, `resources/views/galaxy/index.blade.php:43–44`  
```blade
data-objects="{{ json_encode($objects->values()) }}"
```
`{{ }}` escaped HTML-Entities in JSON — das macht das JSON ungültig und JS kann es nicht parsen.  
**Fix:** `{{ json_encode(...) }}` → `@json(...)`.

---

### [H2] Globale `onclick`-Funktionen in `system.blade.php` ohne Definition
**Datei:** `resources/views/galaxy/system.blade.php:88, 92, 95`  
`onclick="deactivateFleet()"`, `onclick="activateMoveMode()"`, `onclick="submitHold()"` — diese Funktionen sind in keiner geladenen JS-Datei definiert (nicht in `galaxy.js`, nicht inline). Galaxy-System-Screen ist partiell broken.  
**Fix:** Funktionen in `galaxy.js` definieren oder auf Alpine.js-Direktiven umstellen.

---

### [H3] DST-Behandlung im TickService nicht implementiert
**Datei:** `app/Services/TickService.php:51–52`  
```php
// @TODO: Distinguish summer/winter time (DST) — not yet implemented.
```
Bei EU-DST-Umstellung (März/Oktober) kann die Tick-Berechnung um 1 Stunde falsch liegen.  
**Fix:** Server auf UTC erzwingen (`date_default_timezone_set('UTC')` in AppServiceProvider) und in Config dokumentieren, oder explizit Carbon/UTC nutzen.

---

### [H4] HTML-Injection in Event-Messages
**Datei:** `resources/views/messages/events.blade.php:107`  
```blade
<span class="msg-subject">{!! $text !!}</span>
```
`$text` enthält zusammengesetztes HTML mit partiell unescapten Variablen aus der DB. Hardcoded HTML-Fragmente im PHP-Code (`'<strong>unbekannte Flotte</em>'`) sind falsch geschlossen und potenziell injection-fähig.  
**Fix:** Event-Text als strukturierte Daten speichern und erst im Template rendern, oder alle Teile konsequent mit `e()` escapen.

---

### [H5] `GameTick`-Command ist ein 1028-Zeilen-Monolith
**Datei:** `app/Console/Commands/GameTick.php`  
11 Tick-Schritte sind inline in einer Klasse. Nicht testbar, nicht isolierbar, schwer zu debuggen.  
**Fix:** Jeden Schritt in eine eigene Service-Klasse extrahieren (`FleetMovementTickService`, `CombatTickService`, `DecayTickService`, etc.). `GameTick.php` orchestriert nur noch.

---

### [H6] Race Condition: Advisor-Promotion ohne DB-Locking
**Datei:** `app/Services/Techtree/PersonellService.php`  
Bei parallelen Tick-Läufen könnte ein Advisor mehrfach promotet werden.  
**Fix:** Promotion in `DB::transaction()` mit `->lockForUpdate()` absichern.

---

### [H7] Fehlende Security-Tests für Cross-Colony-Zugriff
**Datei:** `tests/` (fehlt komplett)  
Es existiert kein Test, der verifiziert, dass ein User keine fremden Colonies manipulieren kann.  
**Fix:** `SecurityTest` schreiben: `actingAs($user1)->post('colony.tile.explore', ['colony_id' => $user2Colony->id])->assertForbidden()`.

---

### [H8] Hardcoded deutsche Strings in `confirm()`-Dialogen
**Dateien:** `resources/views/fleet/index.blade.php:75`, `resources/views/trade/resources.blade.php:132, 162`  
`onclick="return confirm('Flotte ... wirklich löschen?')"` — nicht lokalisiert, nicht Alpine-konform.  
**Fix:** Entweder `@json(__('fleet.confirm_delete'))` + Alpine-Modal oder Custom-Confirm-Dialog.

---

### [H9] `Colony`-Model schreibt in eine View (`v_glx_colonies`)
**Datei:** `app/Models/Colony.php:16`  
```php
protected $table = 'v_glx_colonies';  // SQLite-View!
```
SQLite-Views sind nicht updatebar. Schreiboperationen schlagen still fehl.  
**Fix:** Schreibzugriffe auf `glx_colonies`-Tabelle direkt leiten; View nur zum Lesen verwenden.

---

### [H10] `mktime()` ohne vollständige Argumente — deprecated in PHP 8.0+
**Datei:** `app/Services/TickService.php:59, 71`  
```php
mktime($calcBegin)  // deprecated, in PHP 9.0 entfernt
```
**Fix:** Auf `Carbon` oder explizites `mktime(h, m, s, month, day, year)` umstellen.

---

## MITTEL — Im nächsten Sprint adressieren

### [M1] Advisor `rank_thresholds`-Index-Semantik unklar
**Datei:** `config/game.php:81–85`  
```php
'rank_thresholds' => [1 => 10, 2 => 20],
```
Index `1` meint "nach Rang 1 braucht es 10 Ticks" oder "für Rang 1"? Korrekte Semantik sollte `[2 => 10, 3 => 20]` sein.  
**Fix:** Semantik im Kommentar klären, Unit-Test für Promotion-Timing schreiben.

---

### [M2] Balance-TODOs für Phase-3g-Gebäude nach Playtest ausstehend
**Datei:** `config/buildings.php:143, 156–159, 171–172`  
`securityHub`, `uplinkStation`, `tradingPost` haben provisorische Werte und unimplementierte Effekte (Uplink Lv2+: Tiefenscan-Kosten −1; Uplink Lv3: Run-Abschluss-Aktion).  
**Fix:** Nach erstem Playtest konkrete Werte eintragen; fehlende Effekte als GitHub Issues tracken.

---

### [M3] Harvester-Platzierungsregel nicht server-seitig enforced
**Datei:** `app/Http/Controllers/Colony/ColonyController.php` (Tile-Platzierung)  
GDD §4 sagt: Harvester nur auf `regolith_*`-Tiles. Im Code wird das nicht geprüft.  
**Fix:** In `ColonyTileService::placeBuilding()` Tile-Typ gegen Building-Typ validieren.

---

### [M4] Fehlender Index auf `colony_resources.colony_id`
**Datei:** `database/migrations/0001_01_01_000013_create_colony_resources_table.php`  
Primary Key ist `(resource_id, colony_id)`. Queries `WHERE colony_id = X` können den Index nicht nutzen.  
**Fix:** `$table->index('colony_id')` zur Migration hinzufügen.

---

### [M5] `user_resources` ohne expliziten Primary Key
**Datei:** `database/migrations/0001_01_01_000018_create_user_resources_table.php`  
`user_id` ist `UNIQUE` aber kein `PRIMARY`. SQLite erstellt implizit einen `rowid` — schlechtes Design.  
**Fix:** `$table->primary('user_id')`.

---

### [M6] Testdata-Inkonsistenz: `current_tick` vs. `since_tick`
**Datei:** `data/sql/testdata.sqlite.sql:68–69`  
Colony 1 hat `current_tick=3` aber `since_tick=20585` (= globalTick). Damit ergibt Sol = 1, nicht 4.  
**Fix:** `since_tick = 20582` (= globalTick − current_tick), um konsistente Testdaten zu erhalten.

---

### [M7] `getFreeSupply()` in `ResourcesService` — ungenutzte öffentliche Methode
**Datei:** `app/Services/ResourcesService.php:204–240`  
Methode ist implementiert aber wird nirgends aufgerufen (nicht in GameTick, nicht in Controllern).  
**Fix:** Entweder entfernen oder als `@internal` dokumentieren mit geplantem Verwendungskontext.

---

### [M8] N+1-Query-Risiko in `LobbyController`
**Datei:** `app/Http/Controllers/LobbyController.php:24–27`  
`calculateScore($run)` im `map()`-Callback macht zusätzliche DB-Queries pro Run.  
**Fix:** Benötigte Relationen in der Haupt-Query per `->with([...])` vorladen.

---

### [M9] Übermäßige Inline-Styles (schwer wartbar)
**Dateien:** `resources/views/fleet/config.blade.php` (~95 Zeilen Inline-CSS), `resources/views/galaxy/system.blade.php` (~46 Zeilen), `resources/views/galaxy/index.blade.php` (~33 Zeilen)  
**Fix:** In dedizierte CSS-Dateien unter `public/css/` auslagern.

---

### [M10] `app.css` referenziert Tailwind statt PicoCSS
**Datei:** `resources/css/app.css:1`  
```css
@import 'tailwindcss';
```
Projekt nutzt PicoCSS + Bootstrap 5 (Legacy). Tailwind-Import ist unerwartet — entweder aktiv und undokumentiert oder toter Code.  
**Fix:** Klären ob Tailwind aktiv ist; falls nicht, Datei bereinigen.

---

### [M11] Fehlende Logging in kritischen Geschäftstransaktionen
**Datei:** `app/Services/BarService.php`, `app/Services/MerchantService.php`  
Kauf, Tausch und Handelsaktionen werden nicht geloggt. Debugging von Spieler-Reports ist dadurch schwierig.  
**Fix:** `Log::info()` mit `colony_id`, `user_id`, `item/resource`, `amount` für alle Transaktionen.

---

### [M12] ROADMAP nicht mit abgeschlossenen Features synchronisiert
**Datei:** `ROADMAP.md`  
Viele Phase-3-Items (3f Berater-Screen, 3g Gebäude, 3h Techtree-Layout, 3i Run-System) sind laut CHANGELOG implementiert, in der ROADMAP aber noch `[ ]` unchecked.  
**Fix:** Abgeschlossene Items mit `[x]` + Datum markieren.

---

## NIEDRIG — Tech-Debt, bei Gelegenheit

### [N1] Hardcoded Building-IDs (Magic Numbers) in Controllern
**Datei:** `app/Http/Controllers/Colony/ColonyController.php:53, 127, 142–143, 150`  
`where('building_id', 25)` — CC, Harvester etc. als Magic Numbers.  
**Fix:** Konstanten oder `config('buildings.commandCenter.id')` nutzen.

---

### [N2] `MoralService::RESOURCE_ID = 12` — hardcodiert
**Datei:** `app/Services/MoralService.php:33`  
**Fix:** `config('game.moral.resource_id', 12)` verwenden.

---

### [N3] Breite Exception-Catches verschlucken Bugs
**Datei:** `app/Http/Controllers/Auth/LoginController.php:48–56`  
`catch (\Throwable)` ohne Typisierung fängt alle Fehler — auch unerwartete — und loggt sie nicht.  
**Fix:** Spezifische Exception-Typen fangen; unerwartete Exceptions re-throwen oder mit `Log::error()` erfassen.

---

### [N4] Legacy-JS-Dateien nicht entfernt
**Dateien:** `public/js/jquery.bootstrap-growl.min.js`, `public/js/test.js`  
jQuery-Plugins die nicht mehr verwendet werden, liegen noch im `public/`-Ordner.  
**Fix:** Löschen (sofern keine Referenzen existieren).

---

### [N5] Fehlende Modell-Scopes
**Datei:** `app/Models/Run.php`  
Nur `scopeActive()` vorhanden. `scopeCompleted()`, `scopeFailed()`, `scopeByUser()` fehlen und werden stattdessen inline als `->where('status', 'completed')` gebaut.  
**Fix:** Scopes ergänzen für Konsistenz.

---

### [N6] Englische Lokalisierungen unvollständig
**Dateien:** `lang/en/buildings.php`, `lang/en/advisors.php`, `lang/en/colony.php`  
Nur Stubs vorhanden — englischsprachige User sehen deutsche Texte als Fallback. (Phase-4-Feature, kein Blocker.)

---

### [N7] SQLite Partial Index ist DB-spezifisch
**Datei:** `database/migrations/2026_04_10_000002_add_unique_advisor_per_colony.php`  
`WHERE colony_id IS NOT NULL` im Unique Index ist SQLite-spezifisch und würde bei PostgreSQL-Migration brechen.  
**Fix:** Dokumentieren in `docs/` dass Schema SQLite-spezifisch ist.

---

### [N8] Fehlende GameTick-Integration-Tests
**Verzeichnis:** `tests/Feature/GameTick/`  
GameTick ist 1028 Zeilen und das Herzstück des Spiels — keine Integration-Tests für einzelne Schritte (Movement, Combat, Supply, Decay).  
**Fix:** Schrittweise Tests je extrahiertem Service schreiben (verknüpft mit [H5]).

---

## Zusammenfassung

| Priorität | Anzahl | Schwerpunkte |
|---|---|---|
| **Kritisch** | 9 | Serialize/RCE, jQuery-Ausfall, Supply-Bug, Auth-Lücken, XSS, fehlende Transaktionen, AP-Items, Knowledge-Gate |
| **Hoch** | 10 | JSON-Escaping, Broken Galaxy-UI, DST-Tick, HTML-Injection, Monolith GameTick, Race Condition, Security-Tests, Colony-View-Bug |
| **Mittel** | 12 | Balance-TODOs, DB-Indizes, Testdata-Inkonsistenz, N+1-Queries, Inline-CSS, Tailwind-Mystery |
| **Niedrig** | 8 | Magic Numbers, Legacy-JS, Exception-Handling, Fehlende Scopes |
| **Gesamt** | **39** | |

---

*Audit durchgeführt: 01.06.2026. Nächste Überprüfung empfohlen nach Abschluss der kritischen Fixes.*
