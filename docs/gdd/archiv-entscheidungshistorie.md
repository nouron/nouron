# Archiv: Entscheidungshistorie

Aus dem GDD ausgelagerte Begründungen zu Vorgängerregeln. Das GDD beschreibt nur den heutigen Soll-Zustand.

## Sammlung aus dem GDD-Sweep (2026-10-25)

- **Harvester-Transit (2026-06-28):** Distanzabhängige Transit-Zeit (z. B. 1 Sol pro 2 Hex) geprüft und verworfen: doppelte Strafe zur AP-Distanzkosten, blockiert Reparaturen im Transit (Decay-Spirale).
- **Resource-Cap / Depot (2026-06-22):** Lagerlimit geprüft und verworfen, Depot-Gebäude entfernt (Migration `2026_06_22_000001_remove_depot_building.php`); Wiedereinführung bei Run-Modifiern mit Überschuss-Mechanik denkbar.
- **Supply (2026-06-08 / 2026-08-02):** Schiffe aus der Supply-Last entfernt. Supply streichen geprüft und verworfen (trägt Tiefe, Verpflegung, Wohnhabitat, CC-Ausbau). Kolonisten-Framing von Phase 4+ vorgezogen. Seit 2026-10-15 speisen Kenntnisse den Cap nicht mehr (Variante B, Owner); `knowledge_cap_per_level` entfiel.
- **Handelsvertrag (F3, Anfang 2026-09):** Passive Cr/Sol-Einnahme für Konsul plus Cantina gestrichen (Paritätsverletzung); Ersatz ist der Cantina-Begegnungspool (2026-09-09). Konsul-Effizienz-Effekt (A13, 2026-09-20) gestrichen; `trade` senkt keine Baukosten und beschleunigt keinen Ausbau (A13). Kanal 2 „Nexus-Handelsschiffe" (F5, 2026-09-08) gestrichen. Handels-Ereignis `trade_blocked` (2026-10-23) gestrichen.
- **Effekt-Hysterese (F2, revidiert 2026-09-08):** Erwogen, dass der Effekt bei SP 0 ausfällt und erst ab größerem Reparaturanteil zurückkehrt; verworfen (Kaskaden-/Softlock-Risiko).
- **Zustandsbasierter Missionsausgang (2026-07-04):** Geprüft und verworfen (Doppelbestrafung langer Missionen, Würfel-Fail verletzt „Opportunitätskosten statt Strafe").
- **Rückkehr-Garantie:** Ersetzt die frühere feste Prozent-Sperre, bei der ein Schiff starten durfte, das unterwegs ausfiel.
- **Berater-Burnout / Außenmissionen (Owner 2026-10-23):** Nie im Code, zurückgestellt; Entwürfe in `archiv-berater-aussendienst-burnout.md`.
- **Phase-1-Kalibrierung (Messung 2026-10-02, 8–9 Seeds):** Mit altem Startbestand Median Sol 12–13; geringerer Startbestand hob den Median in den Korridor (Sol 14–18). Veraltet seit 2026-10-15 (alte Abschlussbedingung CC Lv3). Sturm-Reserve im Startbestand gestrichen.
- **Countdown:** Zusätzliche Kopplung an Phase-2-Sol 80 entfernt (A45). **Aufgabenmessungen:** `task_engineering_output` früher über Status-Punkte, `task_expedition_coverage` früher über erkundete Zonen-Tiles; beide ersetzt.
- **Schuldentilgung:** Die Annahme (F3/A8), Überschüsse tilgten `nexus_debt` automatisch, traf nie zu.

## Pacing, Phase 1, Run-Aufgaben

- **Phase 1 (Owner 2026-10-15/16):** Phase 1 ist der Grundaufbau (nicht mehr CC Lv3 mit zwei Pfadgebäuden); Frist Sol 20 (vorher Sol 30, Owner-Korrektur 2026-10-15); Ziel Sol 12–13 (Minimum), Sol 14–16 (mit Extras). Steigende AP je CC-Ausbaustufe seit 2026-10-15. Leiter vorher auf Phase-1-Ende Sol 15–20 gerechnet. Messung vom 2026-10-02 gilt nur für die alte Bedingung.
- **AP-Deckel pro Ziel und Sol (2026-10-16), Reparatur ausgenommen (2026-10-17).** Phasen-Zuordnung der Berater-Slots (2026-10-15).
- **Run-Aufgaben (Owner 2026-09-26/30):** Kalibrierregel, steigende Kontrollpunkt-Leiter und „Fortschritt statt Erfüllung" am 2026-09-26; der Folgeschritt zu `task_credit_reserve` am 2026-09-30 ersetzte die Fassung vom 2026-09-26. Frühere Konstanten in `RunProgressService` und der Block `run.nexus_milestones` sind entfernt.
- **Verpflegung / Kolonisten (Owner 2026-10-25):** 1 Organika versorgt 1 Kolonisten pro Sol; verpflegt werden alle Kolonisten. Kolonisten-Modell, Training/Gespräche, Labor-Deckel und Phase-2-Übergang am selben Tag.
- **Weitere Datierungen:** Knappheitsordnung (2026-08-02); Direktimport (F5, 2026-09-08); Harvester konstanter Ertrag statt Rampe (2026-08-10, F4 2026-09-08); Reserve beim Platzieren (2026-09-24); Besetzungsanteil nur auf Rohstoffproduktion (2026-09-24); Kolonistengefahren-Kategorien (2026-09-03); Kenntnisse in jedem Run (F6, 2026-09-08); Regolith-Verkauf (T10, 2026-09-28); Vertrauens-Nachwirkung (2026-10-23).

## Wohnraumverlust (A14 / A2)

- **2026-09-24 (Owner):** Der Decay-Multiplikator bei Überkapazität (`decay.overcap_factor`) wurde gestrichen. Er erhöhte die Instandhaltung aller Gebäude still und war die Quelle des Falls „ab Sol 50 steht der Spieler still, ohne die Ursache zu erkennen". Zusätzlich hätte er die Kaskade befeuert: Wohnraum verloren, alles verfällt schneller, noch mehr Wohnraum verloren.
- **2026-10-25 (Owner, Option A2; Herleitung in `docs/superpowers/specs/2026-10-25-arbeiter-modell.md` und `2026-10-25-kolonisten-modell.md`):** Kolonisten gleich Wohnraum. Das ältere Modell (Obdachlosen-Frist mit eskalierendem Vertrauens-Malus-Streak, Abwanderung als Unterbesetzung, automatische Rückkehr, Aktion „Wegschicken“, Config-Block `game.overcap`, Ereignisse `colonists_left` und `colonists_dismissed`) wurde ersetzt. Eine Frist hätte einen gespeicherten Zusatzzustand und eine Anzeige „Obdachlose“ verlangt, die dem Kolonisten-Begriff widerspricht. Das Arbeiter-Modell (Gebäude ohne Kolonisten bauen, Arbeiter getrennt zuweisen) wurde geprüft und nicht verfolgt; Neubewertung nach der Closed Beta, falls Spieler an der Sperre selbst scheitern.
