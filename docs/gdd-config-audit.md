# GDD ↔ Code/Config Drift-Audit

Stand: 2026-08-21, nach GDD-Konsolidierung (ADR 0004). Ersetzt die Fassung vom 2026-08-02 — die meisten dortigen Zahlen-Drifts sind durch die Konsolidierung obsolet geworden, ein Rest bleibt unten unter "Bereits bekannt" bestehen. Wo GDD und Code auseinanderlaufen, gilt Code/Config als kanonisch (CLAUDE.md) — GDD ist nachzuziehen.

## 1. Verbleibende Zahlen im GDD-Prosa-Text (Verstoß gegen ADR 0004)

- `docs/GDD.md:2153-2172` §13 „Rang-System" — Tabellen AP-Bonus +4/+7/+12, Gesamt-AP 10/13/18, Upkeep 10/25/50, Einstellungskosten 300/400/500/350. Zusätzlich inhaltlich falsch (siehe §2). Eigener Task, hohe Priorität.
- `docs/GDD.md:1102` — Decay-Dezimalwert-Range „0.05–0.3 SP/Sol" + Beispielrechnung. Verstößt gegen ADR 0004 und ist falsch. Klein, schnell.
- `docs/GDD.md:3166-3170` — Nexus-Schulden „3.000 Cr Vorschuss", „Schuldenlimit 12.000 Cr". `12000` ist laut §18.6 ohnehin nur hardcodiert in `RunProgressService`, kein Config-Key — an das dortige TODO ("Config-Key anlegen") drankoppeln.
- `docs/GDD.md:3185` Highscore-Formel — als Formel-Beispiel gekennzeichnet, von ADR 0004 erlaubt. Kein Task.

## 2. Mechanik-Beschreibungen, die nicht mehr zum Code passen

- `docs/GDD.md:2149-2215` §13 „Rang-System" + „Verfügbare AP" — beschreibt Vor-Konsolidierung-Modell (fixer Gesamt-AP pro Berater). Tatsächlich: gemeinsamer Pool, `ap.base=12`, `ap_per_rank=[1=>2,2=>3,3=>4]`, `upkeep=[1=>10,2=>25,3=>35]` (nicht 50). „Verfügbare AP"-Absatz markiert Grundwert noch als offen, obwohl `ap.base=12` seit 2026-08-03 freigegeben ist. **Kapitel komplett neu schreiben — höchste Priorität.**
- `docs/GDD.md:1464-1489` §10 „Zwei Effekt-Ebenen" — beschreibt Primär-/Sekundäreffekt-Modell mit Berater-Zuweisungspflicht + `config('game.knowledge_effects.*')` (Key existiert nicht mehr). Tatsächlich (`config/knowledge.php`, `ProjectBonusService.php:22-32`): `ap_cost_reduction_per_lv`/`bar_offer_boost_per_lv` wirken direkt aus Kenntnis-Level, keine Zuweisungsprüfung. Eigener Task.
- `docs/GDD.md:3063-3078` §15 „Aufgabenpool" — Tabelle mit 10 Einträgen (Lücke bei 6). Code (`RunProgressService.php:27-51`) hat nur 8 Tasks. „Selbstversorgung"-Kriterium im GDD (Werkstoff+Credits-Saldo) ≠ Code (`regolith>25 && organics>75 && supply>0`). Bereits 2026-08-18 in alter Audit-Datei vermerkt, weiterhin ungefixt.
- `docs/GDD.md:2803,2986,3002` §14 — Kapitel „Moralsystem", Text sagt „Config-Schlüssel `moral` hinzufügen" als TODO. Tatsächlich: Umbenennung `moral`→`trust` abgeschlossen, `config/game.php:414` hat `trust`-Block produktiv. Liest sich wie Pre-Implementation-Hinweis, ist aber längst erledigt.

## 3. Fehlende Dokumentation

- Kenntnis-Effekte `ap_cost_reduction_per_lv`/`bar_offer_boost_per_lv` (Spec 2026-08-15) — weder in §10 noch §12 „Bar-Level-Progression" erwähnt. Im selben Task wie §2/§10 erledigen.
- `promotion_costs` (`config/game.php:318`, [2=>150,3=>250]) — in §13 nicht erwähnt, nur in game-reference.md. Im Rang-System-Task miterledigen.
- `bioFacility.max_level=8` (seit 2026-07-20 gesetzt) — Anhang A.4 listet Instanz-Deckel noch als offene Frage. Evtl. nur Instanz- vs. Level-Deckel-Verwechslung, kein eigener Task, bei nächster Anhang-A-Pflege prüfen.

## 4. game-reference.md Drift

- `docs/game-reference.md:121-125` „Schiffe" — Supply-Kosten Frachter=6/Korvette=14. Tatsächlich `config/ships.php`: `supply_cost=0` für alle Schiffe (seit 2026-06-08, Schiffe kosten kein Supply). Echte, verifizierte Differenz — eigener kleiner Task.
- `docs/game-reference.md:229-230` — Promotion-Zeilen-Beschriftung vertauscht/unklar (Rank 2→3 doppelt statt Rank 1→2 + Rank 2→3). Trivial.
- Stichproben ok: Berater-Upkeep 10/25/35, `ap_per_rank` +2/+3/+4, Promotion-Kosten 150/250, Hangar-Baukosten 95 Rg, Decay-Klassen — konsistent mit Config. game-reference.md insgesamt aktueller als GDD-Prosa.

## 5. Terminologie-Drift

- `docs/GDD.md:38,2803` Kapitelüberschrift „Moralsystem" statt „Vertrauenssystem" — letzte Strukturstelle mit altem Begriff (Fließtext im Kapitel nutzt bereits „Vertrauen"). Klein.
- Config-Schlüssel `moral` in Zeile 2986/3002 (siehe §2).
- Grep auf Angriff/Kampfsystem/Fleet-Commander/Steuern/Forschungshandel/Tradecenter/Rassen/Battlecruiser/Koloniekommandant: keine Treffer außer historisch gekennzeichnete Stellen (§1.1). Sprachregeln sonst sauber eingehalten.

## Priorisierung (empfohlene Task-Reihenfolge)

1. §13 „Rang-System" + „Verfügbare AP" komplett neu schreiben (Kategorie 1+2+3 kombiniert)
2. §10 „Zwei Effekt-Ebenen" an tatsächliche Kenntnis-Effekt-Implementierung anpassen, toten Config-Verweis entfernen, `bar_offer_boost_per_lv` in §12 nachtragen
3. §14-Überschrift + moral→trust in Zeile 2986/3002 korrigieren
4. §15 Aufgabenpool-Tabelle auf 8 echte Tasks korrigieren (oder auf §18 verweisen)
5. `docs/GDD.md:1102` Decay-Range korrigieren/auf game-reference.md verweisen
6. `docs/game-reference.md` Schiffs-Supply-Kosten auf 0 korrigieren

## Bereits bekannt, nicht neu erhoben (aus Audit 2026-08-02, weiterhin gültig)

- `data/sql/testdata.sqlite.sql`: Hangar/Krankenstation/Cantina `decay_rate` veraltet (0.67/2.0/1.0 statt 0.60/0.80/0.80), Cantina `supply_cost=4` statt 6 — relevant für nächste `ResetPlayer.php`-Szenario-Pflege.
- ⚠️ `harvester.max_level`: DB hatte `1` vs. Config `8` — bei nächstem `game:sync-config`-Lauf prüfen, ob noch aktuell.
- Sieben Gebäude mit `max_level=NULL` (Sciencelab, Temple, Agrardom, Hangar, Krankenstation, Monument, Cantina) — Kostenkurve läuft ohne Endpunkt, offen ob gewollt.
