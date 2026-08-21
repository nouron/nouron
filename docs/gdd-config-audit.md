# GDD ↔ Config Drift Audit

Gefunden bei der Durchsicht am 2026-08-02, alle unabhängig von den Designfragen und **alle noch offen**. Wo GDD und Code auseinanderlaufen, gilt laut CLAUDE.md der Code bzw. die Config als kanonisch — das GDD ist nachzuziehen, nicht umgekehrt.

## Dokumentierte Drifts

| Ort | GDD/Doku sagt | Config/Code sagt |
|---|---|---|
| §4 „Level-Up" | CC-Upgrade = Ziel-Level × **30** Rg (Lv2 = 60) | `cc_upgrade_regolith_per_level = 20` (Lv2 = 40) |
| §7 „Fraktionaler Decay" | Dezimalwert 0,05–0,3 SP/Sol | tatsächlich 0,33–2,0 |
| §6 Config-Block | listet `supply.ship_cost` (Korvette 14, Frachter 6) | Key existiert in `config/game.php` nicht; die §6-Prosa sagt korrekt „Schiffe kosten kein Supply" |
| §13 „Rang-System" | Gesamt-AP = 6 + Bonus (10/13/18) | mit dem gemeinsamen Pool obsolet (§13.1) |
| `config/knowledge.php` (Kommentar) | „base 6 + Rang-1-Bonus 4", „Rang 2 bei 10 aktiven Ticks" | `rank_thresholds = [1 => 15, 2 => 45]`; Grundwert ändert sich mit §13.6 |
| `config/advisors.php` | `strategist` (id 93) + Slot-5-Kommentar | Stratege zurückgestellt (§13) |
| `data/sql/testdata.sqlite.sql` | Hangar supply 6, Cantina 4, Krankenstation decay 2.0 | `config/buildings.php`: 4 / 6 / 0.67 — Testfixture ist auf dem Stand **vor** dem Pfadwahl-Rebalancing 2026-06-28 |
| §15 „Aufgabenpool" (Prosa, Z. 3423–3444) | listet 10 Aufgaben mit anderen Mechaniken (z. B. „Selbstversorgung" prüft Werkstoff-Vorrat + Credits-Saldo) | tatsächlich 8 Tasks (`RunProgressService::TASK_TARGETS`/`TASK_CATEGORIES`), z. B. `updateSelfSufficiency()` prüft `regolith > 25 && organics > 75 && supply > 0` — keine Werkstoffe, kein Credits-Saldo. §18 ist die autoritative Quelle (§18.5-Hinweis „§15-Prosa ist alt"), restated die Liste dort aber nicht vollständig — gefunden 2026-08-18, nicht neu, nur vorher nicht explizit hier erfasst |

## ⚠️ Akut: `harvester.max_level` — DB und Config widersprechen sich

**Die laufende Datenbank hat `max_level = 1`, `config/buildings.php` hat 8.** `game:sync-config` schreibt `max_level` aus der Config in die DB (`SyncConfig.php` Z. 130) — **ein Sync-Lauf setzt den Harvester still auf 8 zurück.** Die Config ist vor jedem weiteren Sync anzugleichen. `data/sql/testdata.sqlite.sql` hat ebenfalls bereits `max_level = 1`, ist hier also mit der DB konsistent.

**Nebenfolge:** Die Glockenkurve aus PR #220 ist für den Harvester wirkungslos. `game.production_curve[27]` definiert Level 1–8, aber bei `max_level = 1` greift nur der erste Eintrag — **8 Rg/Sol, dauerhaft**. Der Config-Kommentar („Growth beyond Lv8 comes only from Kenntnisse/Missionen/Handel") beschreibt einen Zustand, den es in der DB nicht gibt. Beim Agrardom (`max_level = NULL`, unbegrenzt) wirkt die Kurve dagegen voll.

**Sieben Gebäude haben `max_level = NULL`** (unbegrenzt): Sciencelab, Temple, Agrardom, Hangar, Krankenstation, Monument, Cantina. Für sie läuft die `f(L)`-Kostenkurve aus §13.6 ohne natürlichen Endpunkt weiter — bei Lv10 wäre `f` = 4,2, bei Lv15 = 6,2. Ob das gewollt ist oder ob diese Gebäude Deckel brauchen, ist offen.

## ✅ Erledigt: `ap_for_levelup` verifiziert (2026-08-02)

Owner hat die laufende DB geprüft. Ergebnis: **`ap_for_levelup` ist überall 10**, einzige Ausnahme Monument (50) mit 20. Die Migration `2026_04_17_000003_calibrate_building_ap_costs.php` (CC 10 / die meisten 20 / Hangar 30) ist **nicht aktiv** — entweder zurückgerollt oder später überschrieben.

Damit ist die Onboarding-Budgetrechnung (`gdd/onboarding.md` §16.5) korrekt und die Kalibrierung in §13.6 steht auf der Basis, die sie unterstellt hat.

**Aber: eine flache 10 über alle Gebäude ist kein Balancing, sondern ein Default.** Dass der Wert die playgetestete Rampe reproduziert, macht ihn nicht richtig — es macht ihn nur konsistent mit dem, was bisher gespielt wurde. Er gehört zu den Platzhaltern und ist bei der Herleitung der Projektkosten frei wählbar.

**Bei Umsetzung mitzuziehen:** `app/Console/Commands/ResetPlayer.php` — alle fünf Szenarien (`pre-phase2`, `phase2`, `near-fail-trust`, `near-deadline`, `objectives-done`) haben hartcodierte `supply`- und `regolith`-Werte samt Herleitungskommentaren, die an `ap_for_levelup` und den Supply-Formeln hängen.
