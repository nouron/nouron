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
| Pro-Ziel-AP-Deckel pro Sol gilt als Schema für **alle** Berater-Typen (Baumeister: Gebäude; Forscher, Pilot, Händler: je eigene Ziele), Basis + Rang-Bonus je Berater; Kenntnisse können Deckel unterschiedlich beeinflussen (Owner 2026-10-16). Baumeister-Deckel `base` 4 ist aktiv | vorläufig | Messung zeigt, dass die Deckel Phase 2+ bremsen |
| Deckel für Pilot und Konsul nur vorbereitet (`base` null); Rang-Bonus aller Berater = bestehender Pool-Beitrag (2/3/4); Belohnungs-AP (Daten-Sweep, Cantina-Bonus, Gutscheine) vom Deckel ausgenommen; Kenntnis-Deckel (Analytiker) zuerst messen, dann entscheiden (Owner 2026-10-16) | vorläufig | Messung zeigt Labor-First benachteiligt |
| Reparieren bleibt vom Deckel ausgenommen (Sicherheitsventil gegen Verfall, erzeugt keinen Fortschritt; gemessen ~1,8 AP je Sol, ~9 % des AP-Zuflusses; Owner 2026-10-17, Q42) | vorläufig | Reparatur-Spitzen (gemessen Max. 20 AP in einem Sol) werden im Handtest zum Problem; dann eher `regolith_per_click` erhöhen als deckeln |
| Deckel wächst **nicht** mit der Zielstufe (Hebel 2 verworfen, Owner 2026-10-16) | fest | – |
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
- **Phase-2-Messung (gemessen 2026-10-16):** Der Deckel verschiebt CC Lv5 von Sol ~34–40 auf ~49–56 (siehe Messhistorie); die gerechnete Erwartung (Sol 33–40) war zu optimistisch. Ob das passt, hängt am Fahrplan (Sieg-Korridor). Ungenutzte AP bei Hangar/Cantina im Mittelspiel, unabhängig vom Deckel (gemessen, 8 Seeds je Zelle, Sol 12–45): Idle-AP-Anteil am Zufluss Deckel aus Labor 11 %, Hangar 26 %, Cantina 31 %; Deckel an 13 / 33 / 45 %. Erste Deutung (fehlende Housing-Instanz im Sparziel) wurde **widerlegt**: Der Bot-Fix dafür griff in keinem Lauf, Idle-Anteil und Sciencelab-Sol blieben unverändert (Hangar 31,5 % → 31,5 %, Sciencelab Lv1 ~Sol 34–36), weil Housing 28:2 schon in Phase 1 steht. Gemessener Ablauf Hangar: bis ~Sol 21 blockiert Supply (frei 4, Sciencelab braucht 6); ab CC3 (~Sol 24, Supply-Kappe 26 → 34) nicht mehr, dann scheitert die Platzierung an Rg ('hold', ab Sol 31 'resources'): das Sciencelab kostet 120 Rg, der Bot kauft vorher CC Lv3/4, Hangar Lv3 und Housing-Stufen. Ursache ist das **Zielranking** (Sciencelab-Platzierung verliert nach Wert/Rg gegen CC- und Stufen-Schritte), nicht Credits (3000–5000 liegen brach) und nicht das CC-Gate. Forschung braucht Sciencelab Lv1, daher Wissen ~0. Offen: Ranking des nächsten Pfadgebäudes, Idle-AP-Forschung bei Cantina (445 AP bei vorhandenem Labor), und ob Hangar/Cantina ein Forschungs-Äquivalent als AP-Senke brauchen (Designfrage). Werkzeug: `tools/ap-idle.php`.
- **AP-Deckel nach Phase 1 (Owner-Auftrag 2026-10-16):** Der Pool bleibt unverändert, der Deckel begrenzt nur AP je Gebäude und Sol. Gerechnet: CC-Ausbau (20/40/60/80 AP) hat bei Deckel 6/7/8 eine Mindestbauzeit von ~28 Sol (Lv5 frühestens Sol ~40); Ränge des Baumeisters kommen erst nach 15 bzw. 45 aktiven Sol. Zu klären: Rang-Boni (Annahme +2/+3/+4), andere Bonusquellen (Kenntnisse, Projekte); ein Deckel-Wachstum mit der Zielstufe ist **verworfen**. Der Deckel soll außerdem für die anderen Berater gelten (Forscher: Forschung, Pilot: Erkundung/Flüge, Händler: Handel); Zuordnung Ziel/Basis/Bonus je Berater ist offen (Designer-Vorschlag angefordert). Erst messen (Phase-2-Verlauf mit Deckel an/aus bis Sol ~60: CC-Stufe je Sol, ungenutzte AP), dann entscheiden.
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
| Q21 | AP-Deckel `base` 4/5/6 (wirksam 6–8, Baumeister in Sol 0), beide Profile | Sol 11–13, 0 Frist-Fails | Zellen kaum unterscheidbar: wirksamer Deckel 6/7/8 ergibt Kette 10/9/9 Sol. Rg-Puffer gegen 445 Rg gemessen 53–89 (siehe Korrektur) |
| Puffer-1 | Deckel 4 + Uplink-Platzierung 80 → 40, beide Profile | Sol 13 (Cantina Default 11, lean 14) | Platzierungskosten wirkten (Uplink 65 statt 105); Puffer gegen Minimum 340: Median 158–198 |
| Phase-2 | Deckel an (`base` 4) gegen aus, Default-Profil, bis Sol 60, 24 Läufe je Variante | CC Lv3/4/5 (Median-Sol, an/aus): Cantina 21/14, 31/23, 51/34; Hangar 23/17, 36/26, 56/36; Labor 22/16, 30/24, 49/40 | Deckel bremst CC um ~6–7 Sol (Lv3) bis 10–20 Sol (Lv5); Phase-2-Start Sol 11–13 statt 9; 0 Frist-Fails. Ungenutzte AP bei Hangar/Cantina im Mittelspiel hoch **auch ohne Deckel** (Median 15–17 bei Sol 20/30), Labor 0 (Forschung). |
| Bot-Opt1 | Bot-Ranking: nächstes Pfadgebäude vor Stufen/CC Lv4 (nur Bot), Default-Profil, bis Sol 45, 8 Seeds je Eröffnung | Sciencelab platziert Hangar Sol 32,5 → 26, Cantina 29,5 → 24; Sciencelab Lv1 Hangar nur 3 von 8 bis Sol 45; Idle-Anteil Hangar 31,5 → 39,9 %, Cantina 43,6 → 39,8 %, Labor 12,5 → 10,2 %; CC Lv4 +4 (Hangar), +8 (Cantina), +5,5 (Labor) Sol später; Phase-2-Start unverändert | Hypothese nicht erfüllt. Zweiter Bot-Fehler (gemessen): Der Lv0→1-Schritt eines Pfadgebäudes wird nur über einen λ-abhängigen Slot-Term bewertet; bei Leerlauf-AP fällt λ gegen 0, der Schritt wird nie angeboten (Rückkopplung, auch in der Baseline). Platzierungs-Priorität kostet CC Lv4/5, nur sinnvoll zusammen mit dem Lv1-Fix. MySQL-Deadlock-Flake bei `--concurrency=12` (`delete from bar_encounters`). |
| Bot-Opt1b | Bot: Lv0→1 eines Pfadgebäudes bekommt lambda-unabhängigen Unlock-Wert (`w_path_open` 30) zusätzlich zur Pfad-Priorität aus Opt1, Default-Profil, bis Sol 45, 8 Seeds je Eröffnung | Idle-Anteil Hangar 31,5 → 21,8 %, Cantina 43,6 → 27,7 %, Labor 12,5 → 10,2 %; Sciencelab Lv1 Hangar Sol 36 → 27 (8 von 8), Cantina 38 → 25 (8 von 8); CC Lv4 Hangar 36 → 38 (nur 5 von 8 bis Sol 45), Cantina 30 → 40, Labor 30 → 35,5; CC Lv5 fast nie bis Sol 45; Phase-2-Start und CC Lv3 unverändert; 0 Frist-Fails | Leerlauf-Ziel erreicht (nachgerechnet mit `tools/ap-idle.php`). Ziel "CC Lv4 höchstens 4 Sol später" nicht erreicht; vermutete Ursache: Pfad-Priorität aus Opt1 (nicht isoliert). Nächste Messung: Unlock-Wert ohne Priorität, bis Sol 60 (CC4/CC5 sonst abgeschnitten). Gleiches Muster (Lv0→1 ohne lambda-unabhängigen Wert) möglich bei Agrardom/Uplink nach Phase 1, nicht beobachtet. |
| Q19 | Profil `lean-explore`, Grund-AP 12 / 7 / 6 | Sol 9 / 10 / 11 (Spanne 7–11) | Erkundungs-AP 0 statt 45; Rg-Puffer ≈ −15 (Rg-gebunden); Labor steckt freie AP in Forschung (nicht vergleichbar). Hinweis: Batchgrenzen per Zeitstempel getrennt, `--since` allein mischt Batches. |

## Korrektur 2026-10-16 (Rg-Minimum und Puffer-Maß)

- Das "Minimum 445 Rg" war **falsch gerechnet**: Wohnhabitat Lv1 ist beim Start schon da. Das echte Minimum ist Agrardom 95 + Uplink 105 + Pfadgebäude 120 + CC Lv2 60 = **380 Rg** (mit Uplink 65: 340). `tools/phase1-budget.php` berechnet es jetzt aus der Config (`--min=auto`, pro Eröffnung).
- Mit dem korrekten Minimum liegt der Rg-Puffer bei Phase-1-Ende (Sol 12–13, AP-Deckel 4) im Median bei **118–158 Rg** (Uplink 105) bzw. 158–198 (Uplink 65). Das Ziel 100–250 wird damit **ohne Kostensenkung** erreicht (Zahlen bei Uplink 105 sind aus den Läufen umgerechnet, nicht separat gemessen; sie stimmen mit den Zellen "Deckel 4/5/6" gegen 445 plus 65 überein).
- Bot-Befund (nicht behoben): Der Bot kauft in Sol 0 ein **zweites Wohnhabitat** (65 Rg), das bis Phase-1-Ende auf Stufe 0 bleibt. Für den Menschen-Puffer irrelevant, für den Bot sind es 65 tote Rg.

## Änderungen
- 2026-10-16: Rg-Minimum von 445 auf 380 korrigiert, Puffer-Maß dynamisch (Config statt fester Zahl).

- 2026-10-16: Dokument angelegt.
