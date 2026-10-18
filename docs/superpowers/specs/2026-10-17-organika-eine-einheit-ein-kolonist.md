# Organika: eine Einheit ernährt einen Kolonisten pro Sol

Stand: 2026-10-17. Status: **Zielbild entschieden, Umsetzung erst nach dem Handtest** (ändert Config, Bot und Texte). Quelle der Zahlen: Bot-Läufe `p2dir_cap_on` (24 Läufe, Default-Profil, Deckel an, Median, Sol 0–59) und Config; "gerechnet" ist Rechnung des Designers, nicht gemessen.

## Entscheidungen (Owner 2026-10-17)

| Entscheidung | Status | Auslöser für Neubewertung |
|---|---|---|
| Ein "Kolonist" bleibt = 1 Supply (kein eigenes Bevölkerungssystem) | vorläufig | Messung zeigt, dass "Supply belegt" Arbeitsplätze statt Kolonisten zählt |
| Startvorrat Organika > 0 (ca. 2 Sol Bedarf), damit der Sol-1-Hunger wegfällt | vorläufig | – |
| Hunger bleibt in Phase 1 aus; ab Phase 2 ist Hungerrisiko gewollt (Wohnen ohne Agrardom führt in die Hunger-Vertrauens-Spirale) | vorläufig | Handtest: Spirale unfair |
| Regel für den Spieler: 1 Einheit Organika ernährt 1 Kolonisten pro Sol. **Nicht** einfach alles ×4 skalieren (dann bliebe der Überschuss), sondern die Agrardom-Kurve an die Kolonistenzahl koppeln (Variante V1, dünne Marge) | vorläufig | Messung M1 |
| Vorrat zuerst nur messen; Lagerdeckel/Verfall nur, wenn der Vorrat trotz V1 über 5 Sol Bedarf steigt | vorläufig | M1 |
| Missionsproviant (3 Organika je Sol Distanz) bleibt unverändert, nach M1 bewerten | vorläufig | M1 |

## Ist-Zustand (gemessen)

| Sol | Kolonisten (Supply belegt) | Agrardom | Produktion/Sol | Verbrauch/Sol | Vorrat |
|---|---|---|---|---|---|
| 5 | 10 | Lv1 | 9 | 2 | 28 |
| 20 | 22 | Lv1 | 8 | 5 | 80 |
| 30 | 34 | Lv2 | 20 | 8 | 82 |
| 50 | 74 | Lv3 | 32 | 18 | 168 |
| 59 | 78 | Lv3 | 35 | 19 | 230 |

- Verbrauch = `floor(anwesende Kolonisten / 4)` (`game.food.supply_per_eater` = 4). Agrardom Marginalkurve Lv1/2/3 = 8/12/12.
- Produktion liegt dauerhaft 2–4× über dem Verbrauch, der Vorrat wächst nur (bis 230). **Hunger tritt in allen 24 Läufen genau einmal auf, immer in Sol 1** (Startvorrat 0, keine Produktion), danach nie (Vertrauen −2 am Anfang).
- Es gibt keinen Druck auf Organika; die Agrardom-Stufen 2 und 3 sind Luxus. Das widerspricht dem GDD ("Organika seltener als Regolith").

## Zielbild V1 (dünne Marge), gerechnet

- `supply_per_eater` 4 → 1; Agrardom-Marginalkurve 16/20/30/14 (kumulativ 16/36/66/80); Startvorrat ca. 10 (Annahme). Regel im Spiel: "Ein Wohnhabitat-Level (8 Plätze) braucht 8 Organika pro Sol."
- Marge: Lv1 deckt etwa die Kolonisten bis Sol ~10, Lv2 wird ab Sol ~22 zwingend, Lv3 ab ~Sol 31, Lv4 ab ~Sol 46 (Interpolation aus den gemessenen Kolonistenzahlen). Die Stufen folgen damit dem Wohn-Wachstum und werden zur Entscheidung.
- Phase 1 (Sol 12–13, ca. 18–20 Kolonisten): Lv1 reicht, keine Zusatz-AP.
- Verworfen/Rückfall: V2 (absichtlich knapp, Lv2 schon um Sol 10–12) bricht "Phase 1 ohne Hunger"; V3 (V1 ×1,25, Überschuss in der Cantina verkaufen) nur als Rückfall.

## Messplan

M1 = ein Paket (Faktor, Kurve und Startvorrat gehören zusammen): 24 Läufe, Default-Profil, gleiche Seeds. KPIs: Hunger-Sols je Lauf (Phase 1 = 0), Vorrat bei Sol 20/40/60 (Ziel 1–3 Sol Bedarf statt 80–230), Anteil Sols mit Vorrat unter 1 Sol Bedarf (< 10 %), Sol der Agrardom-Stufen, Phase-1-Ende unverändert (Sol 12–13), Idle-AP (`tools/ap-idle.php`), je Eröffnung getrennt. Siegquote nur beobachten. Danach, falls nötig: M2 Startvorrat/Bot-Regel (`feed_colony`), M3 Agronomy-Skalierung, M4 Missionskosten.

## Anzupassen bei Umsetzung

`config/game.php` (food, Agrardom-Kurve, Startvorrat), Agronomy-Effekt, `ResetPlayer`-Szenarien, Bot-Regel `feed_colony`, `task_self_sufficiency` (`organics_min`), GDD (zahlenfrei), `docs/game-reference.md`, Tests mit hartcodierten Organika-Werten.

## Nicht verifiziert

Ob "Supply belegt" Kolonisten oder Arbeitsplätze zählt (Annahme: Kolonisten, siehe Entscheidung 1); Cantina-Verkaufswerte; ob Organika einen Lagerdeckel hat; Wirkung von Agronomy nach der Skalierung; Marginal-AP der Agrardom-Stufen; aktuelle `feed_colony`-Regel; Reaktion des Bots auf die neue Kurve.
