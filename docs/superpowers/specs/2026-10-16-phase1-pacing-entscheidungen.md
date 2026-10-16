# Phase-1-Pacing: Entscheidungen, Hebel, Messprotokoll

Stand: 2026-10-16. Gehört zu ROADMAP T35 (Pacing) und T31 (Regolith-Wirtschaft). Lebendes Dokument: Entscheidungen dürfen sich ändern, aber nur mit Eintrag unter "Änderungen".

## Zielbild

- Phase 1 (Grundaufbau) endet beim **Minimum-Spieler** im Median bei **Sol 12–13**; Menschen mit Extras bei 14–16. Frist Sol 20 bleibt Notgrenze.
- Es bleibt **Regolith-Puffer für 1–2 Extra-Gebäude/-Stufen** in Phase 1 (Annahme: ca. 100–250 Rg), damit es nicht nur eine richtige Build-Order gibt.
- Labor-, Hangar- und Cantina-First sind **gleichwertig** (hartes Kriterium).
- Erfolgsmaß sind Sol-Zeitpunkte und Ressourcenfluss, **nicht** die Bot-Siegquote.

## Entscheidungen

| Entscheidung | Status | Auslöser für Neubewertung |
|---|---|---|
| Regolith-Baukosten fallen voll beim Platzieren | fest | – |
| Supply-Kosten bleiben fest, bis Variante B (Supply-Cap nur CC + Wohnhabitat) umgesetzt ist | vorläufig | Variante B ist umgesetzt |
| Zielfenster Sol 12–13 (Minimum), 14–16 (Mensch mit Extras) | vorläufig | Erkundungs-Messung zeigt, dass das Fenster nur über Bot-Verhalten erreichbar ist |
| Puffer 100–250 Rg | vorläufig (Annahme des Owners bestätigt) | Spieltest zeigt anderes Bedürfnis |
| Zeit kommt aus einer Nicht-Rg-Bremse (AP), nicht aus höheren Rg-Kosten | vorläufig | Messung zeigt, dass AP nicht der Engpass ist |
| Grund-AP global senken (statt Phase-1-Sonderregel), Boni heben sie später wieder | vorläufig | Phase 2+ wird zäh |
| Einnahmeseite (Start-Rg, Harvester, Geologie) ist Hebel mit T31 | vorläufig | T31-Paket (Start 260) widerspricht |

## Hebel (was wir verändern dürfen)

Rg-Kosten, Werkstoff-Kosten, flache 25 Rg je Ausbaustufe, AP je Ausbaustufe, Grund-AP (`game.ap.base`), Start-Rg (`onboarding.start_regolith`), Harvester-Ertrag. Nicht: Gates, `max_level`, `max_instances`, Phasenzuordnung (Struktur läuft über T34/T35 separat).

## Fixierte Größen (nicht nebenbei ändern)

- Bot-Engine `utility`, Seeds 1–8 × Labor/Hangar/Cantina, `--until-sol=25`.
- Bot-Verhalten: Phase-1-Pflichtgebäude zuerst, keine Extras davor (Fix 2026-10-16, `phase1Allows()` in `BotStrategy`). Der Bot erkundet unbegrenzt.
- Phase-1-Ende = `phase2_start_sol` (RunProgressService: CC Lv2, Harvester/Wohnhabitat/Agrardom/Uplink Lv1, ein Pfadgebäude Lv1, 2 Berater).

## Offene Variablen

- **Erkundung (geklärt 2026-10-16, Q19):** Der Default-Bot gibt ~45 von ~117 Phase-1-AP für `explore_tile` aus. Gebäude-Platzierung braucht keine Erkundung, nur der Harvester-Umzug (`ColonyController.php:406-412`). Profil `lean-explore` erkundet nur dafür: 0 Erkundungs-AP, Phase-1-Ende bei Grund-AP 12/7/6 = Sol 9/10/11 (Default-Profil: 9/12/13).
- **Phase 1 ist in allen Messungen Regolith-gebunden (gemessen):** Rg-Einkommen ist ab Sol 5 konstant ~21/Sol; verfügbares Rg am Phase-1-Ende liegt immer bei ~426–430 (also Bot kauft das Minimum und Phase 1 endet, sobald das Rg reicht). Grund-AP wirkt fast nur darüber, dass der **Harvester später Einkommen liefert** (erstes Einkommen Sol 3 bei Grund-AP 12, Sol 4–5 bei 7, Sol 5 bei 6). Ein Rg-Puffer kostet damit Zeit: ~+5 Sol je 100 Rg. Offen: wie Phase 1 für den Minimum-Spieler länger wird, ohne das Einkommen zu verzögern (Zeit-Bremse statt Rg- oder Grund-AP-Bremse).
- **Grund-AP** 12 → 7 oder 6, davon hängt Phase 1 und das Tempo danach ab (Phase 2+ ungemessen).
- **Regolith-Hebel** für den Puffer: Start-Rg, Pflichtgebäude-Kosten oder Harvester-Ertrag (Q18).

## Messprotokoll

```
PLAYTEST_PROCESS_TIMEOUT=2700 php artisan game:playtest --engine=utility \
  --openings=labor,hangar,cantina --seeds=1,2,3,4,5,6,7,8 --until-sol=25 --concurrency=24
php tools/phase1-budget.php --since=YYYYMMDD_HHMMSS      # UTC aus dem Dateinamen
```

Regeln: eine Änderung pro Messung; vorher Hypothese und erwartete Zahlen notieren; bei Abweichung zuerst mit `phase1-budget` erklären, dann neuer Hebel. Gerechnete Werte (z. B. das Minimum von 445 Rg) sind als "gerechnet" gekennzeichnet und keine Entscheidungsgrundlage ohne Gegenprobe. Der Bot-Batch dauert ca. 4 min.

## Messhistorie (24 Läufe je Zeile)

| Messung | Konfiguration | Phase-1-Ende | Hinweis |
|---|---|---|---|
| 4 (2026-10-16) | Bot vor Fix, Grund-AP 12 | Sol 17–20, 13 Frist-Fails | Bot kaufte CC Lv3 vor dem Uplink |
| 5 | Bot nach Fix, Grund-AP 12 | Sol 8–9, 0 Fails | Minimum bei Sol 8–9 fertig |
| Sweep | Grund-AP 8 / 7 / 6 | Sol 11 / 12 / 13 | Puffer ≈ 0 bei Start 300 |
| Sweep | 7 mit Start 360 / 400, 6 mit 360 | Sol 12 / 12 / 13 | Puffer 59 / 99 / 77 (Median) |
| Q19 | Profil `lean-explore`, Grund-AP 12 / 7 / 6 | Sol 9 / 10 / 11 (Spanne 7–11) | Erkundungs-AP 0 statt 45; Rg-Puffer ≈ −15 (Rg-gebunden); Labor steckt freie AP in Forschung (nicht vergleichbar). Hinweis: Batchgrenzen per Zeitstempel getrennt, `--since` allein mischt Batches. |

## Änderungen

- 2026-10-16: Dokument angelegt.
