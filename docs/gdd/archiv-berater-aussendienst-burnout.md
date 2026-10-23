# Archiv: Berater-Außendienst und Berater-Burnout

**Status: zurückgestellt (Owner-Entscheidung 2026-10-23), nicht umgesetzt.** Beide Features standen nur im GDD und waren nie im Code (kein `config/game.php → advisors.burnout`, keine Spalten `on_mission_until_tick`/`mission_type`, kein `AdvisorMissionService`). Der Text steht unverändert unten, damit die Idee bei Bedarf wieder aufgenommen werden kann. Nicht zu verwechseln mit den **Hangar-Außenmissionen** (Schiffe, GDD §8b) — die bleiben.

**Was im Code bleibt:** Die Spalte `advisors.unavailable_until_tick` wird weiter genutzt, aber nicht für Burnout, sondern für die **Nexus-Sanktion** (ein zufälliger Berater ist für einige Sole gesperrt, `RunProgressService::lockRandomAdvisor`) und die Verfügbarkeitsprüfung (AP-Berechnung, Hangar, Bar).

**Mitgenommene Folge:** Das Rang-Gate für die Vertiefungs-Höchststufe (Spec `docs/superpowers/specs/2026-10-23-pfad-vertiefung.md`, R5) hat ohne Außendienst und Burnout den „wackelnden Zustand“ nicht mehr als Einwand.

---

## Aus §7 — Berater-Burnout

### Berater-Burnout

Berater können nicht dauerhaft auf Hochtouren laufen. Nach langer Aktivität steigt die Wahrscheinlichkeit, dass ein Berater für eine begrenzte Zeit ausfällt — **Burnout**. Der Ausfall ist nicht garantiert, aber wahrscheinlicher, je länger der Berater ununterbrochen aktiv ist.

**Mechanik (probabilistisch):**

```
burnout_chance(tick) = base_chance × growth_factor^(active_ticks / threshold) × rank_dampener(rank)
```

Die Formel benutzt vier Parameter: eine Basis-Burnout-Chance (niedrig bei Spielstart), einen exponentiellen Steigerungs-Faktor (über Arbeitsdauer), einen Schwellwert (wann die Chancen signifikant ansteigen) und einen Rang-Dämpfer (erfahrenere Berater sind robuster).

**Was passiert bei Burnout:**
- `unavailable_until_tick = current_tick + recovery_ticks` (Länge abhängig von Rang: Junior länger, Experte kürzer)
- `active_ticks` wird **zurückgesetzt** (der Berater startet frisch nach der Erholung)
- Der gemeinsame AP-Pool (§13.1) sinkt für die Dauer um den AP-Beitrag dieses Beraters; sein Domänen-Effizienzbonus (§13.3) entfällt ebenfalls
- Protokoll-Ereignis: „[Name] benötigt eine Auszeit — Kolonie-Kapazität vorübergehend reduziert."

Erfahrenere Berater erholen sich schneller — und haben schon durch den Rang-Dämpfer eine geringere Burnout-Chance.

**`active_ticks`-Reset:** Nach dem Burnout startet der Zähler bei 0. Kein „ständiger Burnout“ ist möglich.

> **Designabsicht:** Burnout ist ein seltenes, aber echtes Risiko, das den Spieler dazu bringt, einen Backup-Plan für den Ausfall eines Beraters zu haben. Experten sind robuster, aber teurer — das macht Rang-Aufstieg strategisch wertvoller als nur „mehr AP pro Sol“.

Tabellenzeilen aus §6/§7 (Entropie-Übersicht): *Berater | Burnout-Wahrscheinlichkeit (steigt mit `active_ticks`) | Kumulierte Aktivität | Erholungsphase, Rang-Aufstieg dämpft Risiko* und *Berater-Burnout | AP-Kapazität bei Überbelastung | probabilistisch | Erholungsphase abwarten*. §7 nannte drei Entropie-Vektoren: Gebäude-Decay, Schiffs-Verschleiß, Berater-Burnout.

## Aus §13 — Außenmissionen (Berater-Außendienst)

Drei Beratertypen (Baumeister, Analytiker, Konsul) können für eine begrenzte Anzahl Sole auf eine **Außenmission** entsendet werden — mit denselben Opportunitätskosten (AP fehlen während der Abwesenheit) und einem Bonus bei Rückkehr. Der Raumfahrer erscheint nicht in der Missions-Auswahl.

**Grundprinzip:** Der Berater verlässt die Kolonie für die Missionsdauer vollständig und generiert keine AP. Bei Rückkehr erhält die Kolonie einen Bonus (Missionstyp und Rang). Der Spieler initiiert manuell; kein vorzeitiger Abbruch. Maximal 2 Berater gleichzeitig.

| Beratertyp | Missionsname | Dauer (Sole) | Bonus bei Erfolg |
|------------|--------------|--------------|-----------------|
| Baumeister | Nexus-Notfall-Wartung | 3–5 | Ein beliebiges Koloniegebäude erhält sofort volle `status_points` |
| Analytiker | Datenaustausch mit Forschungsstation | 4–6 | Spieler wählt eine Kenntnis — diese steigt sofort um 1 Level (ohne AP-Kosten, CC-Gates bleiben aktiv) |
| Konsul | Handelsreise | 3–4 | Exklusives Bar-Angebot bei Rückkehr (2 Sole gültig, zusätzlicher Slot neben normalen Bar-Angeboten) |
| Raumfahrer | — | — | Kein Berater-Außenmissions-Pfad — „Außendienst“ läuft über den Schiffs-Dispatch (§8b) |

> **Balance:** Der Analytiker-Bonus (Kenntnis +1 Level kostenlos) war der stärkste Effekt. CC-Gates bleiben aktiv. Ggf. Einschränkung: Bonus gilt nur für Lv1→Lv2 oder Lv2→Lv3.

> **Entfallen (2026-08-02):** Die Strategen-Außenmission „Sicherheitsanalyse“ ist mit der Zurückstellung des Strategen weggefallen.

**Risiko-Mechanik: drei Ausgänge** — Erfolg (voller Bonus), Teilerfolg (halber Bonus, abgerundet), Misserfolg (kein Bonus; die AP haben dennoch gefehlt). Wahrscheinlichkeiten nach Rang gestaffelt (Junior höheres Misserfolgsrisiko; Werte waren für `config/advisors.php` vorgesehen). Kein permanenter Verlust, kein Rang-Abzug.

**Constraints:**

| Regel | Beschreibung |
|-------|-------------|
| Burnout-Sperre | Ein Berater mit gesetztem `unavailable_until_tick` (Burnout) kann keine Mission starten. |
| Missions-Immunität | Ein Berater auf Außenmission kann währenddessen keinen Burnout erleiden; der Burnout-Timer pausiert. |
| Concurrent-Limit | Maximal 2 Berater gleichzeitig auf Mission. |
| Missionsdauer-Transparenz | Das Missions-UI zeigt die verbleibenden Sole bis Rückkehr. |
| AP-Nutzungsrate | Die Run-Aufgabe „Effizienzsprung“ (§15) schließt Außenmissionen nicht aus; der Spieler wägt ab, ob er einen AP-Produzenten opfert. |
| Raumfahrer ausgenommen | Er erscheint in der Missions-Auswahl nicht. |

**Technische Hinweise (nie umgesetzt):** Schema `advisors.on_mission_until_tick` (nullable int) und `advisors.mission_type` (nullable string: `nexus_maintenance`, `data_exchange`, `trade_trip`, `security_analysis`); die beiden Sperrfelder nie gleichzeitig gesetzt; `getTotalActionPoints()` liefert bei `current_tick <= on_mission_until_tick` 0 AP-Bonus; Abschluss in einem `AdvisorMissionService`; Auflösung in Tick-Schritt 12 (erst AP-Update, dann Burnout-Check, dann Missions-Auflösung).

## Aus §13 — Berater-Burnout (Auswirkung auf AP)

Bei Burnout fällt der AP-Beitrag des Beraters für die Dauer der Erholung auf null; der gemeinsame Pool sinkt um genau diesen Beitrag, sein Effizienzbonus in der Domäne (§13.3) entfällt. Beispiel: Ein Senior-Analytiker mit 20 AP/Sol — bei Burnout fehlen diese 20 AP/Sol, aber kein Bereich fällt ganz aus. Dauer nach Rang: Junior 15, Senior 10, Experte 5 Sole (Richtwerte, nie in Config). Die Berater-Übersicht zeigt einen „Pause“-Zustand mit Countdown und ein Protokoll-Ereignis. Der Berater kehrt automatisch zurück, wenn `current_tick > unavailable_until_tick`; der Slot bleibt reserviert. Beim Raumfahrer: Der Navigation-AP-Pool fällt auf den Grundwert.

## Aus §13 — Fog of Information (Satz)

„Wer einen Berater verliert (Burnout, Abwesenheit, Außenmission), spielt dasselbe Spiel mit weniger Kontext.“ — gilt weiter für Entlassung und Nexus-Sanktion.
