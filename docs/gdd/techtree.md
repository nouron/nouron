# Techtree

> Ausgelagert aus [`docs/GDD.md`](../GDD.md) am 2026-08-02. Entitäten-Übersicht, Abhängigkeitsregeln und Grid-Layout.
>
> Kapitelnummerierung und `§`-Verweise beziehen sich weiterhin auf das GDD.

---

## 11. Techtree

Der Techtree ist die Verwaltungsansicht aller ausbaubaren Entitäten einer Kolonie: Gebäude, Kenntnisse, Schiffe und Berater. Er ist kein linearer Forschungsbaum, sondern ein **überschaubares Abhängigkeitsgitter** — die Kommandozentrale (CC) ist das einzige globale Gate, das den Fortschritt reguliert.

Das Designziel: Ein Spieler soll in 30 Sekunden verstehen, was er bauen kann und warum etwas noch gesperrt ist. Kein Micromanagement, keine Forschungsketten die Monate dauern.

---

### 11.1 Entitäten-Übersicht

Die folgende Tabelle listet alle Entitäten im Techtree.

#### Gebäude

Grid-Koordinaten (phasen-lokal) siehe §11.3.

| Key (intern) | Name (DE) | Voraussetzung | Max-Level |
|---|---|---|---|
| `commandCenter` | Kommandozentrale | — | 5 |
| `housingComplex` | Wohnhabitat | CC Lv 1 | 6 Instanzen |
| `harvester` | Harvester | CC Lv 1 | supply-limitiert |
| `bioFacility` | Bio-Anlage | Harvester Lv 1 | supply-limitiert |
| `sciencelab` | Analytik-Labor | CC Lv 2 | supply-limitiert |
| `bar` | Bar / Cantina | CC Lv 2 + Wohnhabitat Lv 1 | supply-limitiert |
| `infirmary` | Krankenstation | CC Lv 2 | supply-limitiert |
| `hangar` | Hangar | CC Lv 2 (Pfadwahl) | supply-limitiert |
| `securityHub` | Sicherheits-Hub | CC Lv 3 | max. Lv 3 |
| `uplinkStation` | Uplink-Station | CC Lv 2 | max. Lv 3 |
| `temple` | Religiöse Stätte | CC Lv 4 | supply-limitiert |
| `tradingPost` | Handelsposten | CC Lv 4 | max. 1 Instanz |
| `monument` | Kolonialdenkmal | CC Lv 5 | supply-limitiert |

Die 13 Gebäude decken alle Spielsäulen ab: Infrastruktur (CC, Wohnhabitat), Produktion (Harvester, Bio-Anlage), Wissenschaft (Analytik-Labor), Flotte (Hangar), Kommunikation (Uplink-Station), Sicherheit (Sicherheits-Hub), Handel (Handelsposten), Wohlfahrt (Bar, Krankenstation, Religiöse Stätte, Denkmal).

#### Kenntnisse

Die 7 Kenntnisse sind das einzige Forschungssystem. Alle setzen das Analytik-Labor voraus. Zusätzlich gelten funktionale Gebäude-Voraussetzungen je nach Kenntnis.

| Key (intern) | Name (DE) | Voraussetzung | Max-Level |
|---|---|---|---|
| `construction` | Bautechnik | Analytik-Labor Lv 1 | 5 |
| `agronomy` | Agronomie | Analytik-Labor Lv 1 + Bio-Anlage Lv 1 | 5 |
| `health` | Gesundheit | Analytik-Labor Lv 1 + Krankenstation Lv 1 | 5 |
| `cartography` | Kartografie | Analytik-Labor Lv 1 + Hangar Lv 1 | 5 |
| `geology` | Geologie | Analytik-Labor Lv 2 + Harvester Lv 1 | 5 |
| `trade` | Handel & Logistik | Analytik-Labor Lv 2 + Bar Lv 1 | 5 |
| `defense` | Verteidigung | Analytik-Labor Lv 3 + Hangar Lv 2 | 5 |

**Begründung:** Das Analytik-Labor als Gate für alle Kenntnisse stellt sicher, dass der Spieler zuerst eine Wissenschaftsbasis aufbaut, bevor er Spezialkenntnisse erschließt. Die zusätzlichen Gebäude-Voraussetzungen verknüpfen jede Kenntnis mit dem passenden Kolonieteil — Agronomie braucht eine Bio-Anlage, Kartografie einen Hangar, Verteidigung ein höheres Analytik-Labor und einen ausgebauten Hangar. Die Kenntnisse Lv4 und Lv5 sind zusätzlich durch das CC-Level gegattet (siehe §11.2 Regel 3).

> **Roguelike-Variabilität:** Pro Run steht nicht der vollständige Kenntnisbaum zur Verfügung — nur eine zufällig gezogene Teilmenge (z.B. 5 von 7). Details in §15 (Run-Struktur).

#### Schiffe

Drei semantisch klare Typen: Drohne erkundet, Frachter transportiert, Korvette kämpft. Kapazitätsskalierung läuft über Hangar-Slots (Anzahl Schiffe), nicht über verschiedene Schiffsgrößen.

| Key (intern) | Name (DE) | Voraussetzung |
|---|---|---|
| `drone` | Drohne | Hangar Lv 1 |
| `freighter` | Frachter | Hangar Lv 2 |
| `corvette` | Korvette | Hangar Lv 3 |

#### Berater (Personal)

Berater erscheinen im Techtree in Spalte 0. Ihre Gates spiegeln die Einführungsreihenfolge im Run wider. Berater-Slots öffnen über zwei Mechanismen: CC-Level (Slot 1) oder den Bau eines spezifischen Gebäudes (Slots 2–5). Max. 5 Slots (1 je Beratertyp).

| Key (intern) | Name (DE) | AP-Typ | Hire-Voraussetzung | Slot |
|---|---|---|---|---|
| `engineer` | Baumeister | construction | CC Lv 1 | 1 (fix) |
| `scientist` | Analytiker | research | Analytik-Labor Lv 1 | 2–4 (generisch) |
| `pilot` | Raumfahrer | navigation | Hangar Lv 1 | 2–4 (generisch) |
| `trader` | Konsul | economy | Bar Lv 1 | 2–4 (generisch) |

> `strategist` (Stratege, `strategy`, Slot 5) ist mit der Zurückstellung des Strategen (2026-08-02) aus dem Techtree entfernt — siehe §13 „Die vier Berater-Typen". Die Spalte „AP-Typ" bezeichnet seit der AP-Zusammenlegung (§13.1) die **Domäne** des Beraters, nicht mehr einen eigenen Pool.

---

### 11.2 Abhängigkeitsregeln

Das Abhängigkeitssystem folgt vier Regeln:

**Regel 1 — CC als Tier-Gate**
Die Kommandozentrale hat 5 Level und schaltet je Level eine Gebäude-Tier frei. Kein Gebäude höherer Tier ist baubar, solange das CC-Level nicht erreicht ist. Die Tiers:

| CC-Level | Freischaltet |
|---|---|
| 1 | Wohnhabitat, Harvester |
| 2 | Analytik-Labor, Krankenstation, Cantina, Hangar (alle drei Pfadwahl-Gebäude ab Lv2 baubar, gestaffelt — siehe §13), Uplink-Station (Lv1) |
| 3 | Sicherheits-Hub; Uplink-Station Lv2 freischaltbar |
| 4 | Religiöse Stätte, Handelsposten |
| 5 | Denkmal; Uplink-Station Lv3 freischaltbar |

**Regel 2 — Funktionale Abhängigkeiten**
Einige Entitäten setzen nicht nur CC-Level, sondern ein konkretes Gebäude voraus:

| Entität | Voraussetzung |
|---|---|
| `bioFacility` | Harvester Lv 1 |
| `bar` | Wohnhabitat Lv 1 |
| `construction` (Kenntnis) | Analytik-Labor Lv 1 |
| `agronomy` (Kenntnis) | Analytik-Labor Lv 1 + Bio-Anlage Lv 1 |
| `health` (Kenntnis) | Analytik-Labor Lv 1 + Krankenstation Lv 1 |
| `cartography` (Kenntnis) | Analytik-Labor Lv 1 + Hangar Lv 1 |
| `geology` (Kenntnis) | Analytik-Labor Lv 2 + Harvester Lv 1 |
| `trade` (Kenntnis) | Analytik-Labor Lv 2 + Bar Lv 1 |
| `defense` (Kenntnis) | Analytik-Labor Lv 3 + Hangar Lv 2 |
| Drohne | Hangar Lv 1 |
| Frachter | Hangar Lv 2 |
| Korvette | Hangar Lv 3 |
| Raumfahrer (Berater) | Hangar Lv 1 |
| Konsul (Berater) | Bar Lv 1 |

**Regel 3 — CC-Level-Cap für Kenntnisse Lv4/5**
Kenntnisse können maximal auf das aktuelle CC-Level ausgebaut werden, sobald sie Lv4 oder Lv5 erreichen sollen. Lv1–3 sind immer erreichbar wenn die Gebäude-Voraussetzungen erfüllt sind. Lv4 erfordert zusätzlich CC Lv4, Lv5 erfordert CC Lv5.

| Kenntnis-Level | Zusätzliche Voraussetzung |
|---|---|
| 1–3 | Nur Gebäude-Voraussetzungen (Regel 2) |
| 4 | Gebäude-Voraussetzungen + CC Lv 4 |
| 5 | Gebäude-Voraussetzungen + CC Lv 5 |

**Regel 4 — Supply als weicher Gate**
Jedes Gebäude und jedes Schiff verbraucht Supply. Supply-Cap ist durch CC-Level und Wohnhabitate begrenzt. Der Spieler kann theoretisch alles bauen wollen, ist aber durch Supply gezwungen, Prioritäten zu setzen. Das ist kein harter Abhängigkeitsbaum, sondern Ressourcendruck. Details in §6 (Supply-Generierung).

> **Keine zyklischen Abhängigkeiten.** Jede Abhängigkeitskette endet beim CC. Ein Deadlock durch wechselseitige Abhängigkeiten ist konstruktiv ausgeschlossen.

---

### 11.3 Grid-Layout (Techtree-Ansicht)

Der Techtree ist in **5 Phasen** aufgeteilt, jede entspricht einem CC-Level-Meilenstein. Jede Phase ist ein **3-Spalten-Grid** (Koordinaten phasen-lokal, 1-indexiert). Pfeile verbinden Abhängigkeiten ausschließlich innerhalb einer Phase — das CC-Level-Gate kommuniziert der Phasen-Header.

**Pfeil-Quellen:**

- Gebäude, Schiffe, Berater: Pfeil von `required_building_id`
- Kenntnisse: Pfeil vom **sekundären Gebäude** (nicht vom Analytik-Labor). Ausnahme: `construction` hat kein sekundäres Gebäude — Pfeil vom Analytik-Labor. Bei phasen-übergreifenden Sekundär-Voraussetzungen wird auf das Analytik-Labor als Phasen-internen Anker zurückgegriffen.

**Vollständige Phasen-Grid-Koordinatentabelle** (row/col phasen-lokal, 1-indexiert):

| Phase | CC-Lv | Entität | Typ | Row | Col |
|-------|--------|---------|-----|-----|-----|
| 1 | 1 | housingComplex | building | 1 | 1 |
| 1 | 1 | harvester | building | 1 | 2 |
| 1 | 1 | bioFacility | building | 2 | 2 |
| 1 | 1 | engineer | personell | 2 | 3 |
| 2 | 2 | sciencelab | building | 1 | 2 |
| 2 | 2 | infirmary | building | 1 | 3 |
| 2 | 2 | bar | building | 2 | 1 |
| 2 | 2 | hangar | building | 2 | 2 |
| 2 | 2 | scientist | personell | 2 | 3 |
| 2 | 2 | trader | personell | 3 | 1 |
| 2 | 2 | pilot | personell | 3 | 2 |
| 2 | 2 | knowledge_construction | research | 4 | 3 |
| 2 | 2 | knowledge_agronomy | research | 5 | 3 |
| 2 | 2 | knowledge_health | research | 6 | 1 |
| 2 | 2 | knowledge_trade | research | 6 | 3 |
| 3 | 3 | securityHub | building | 1 | 1 |
| 3 | 3 | strategist | personell | 1 | 2 |
| 3 | 3 | drone | ship | 2 | 1 |
| 3 | 3 | freighter | ship | 2 | 2 |
| 3 | 3 | knowledge_geology | research | 3 | 1 |
| 3 | 3 | knowledge_cartography | research | 3 | 3 |
| 3 | 3 | corvette | ship | 4 | 2 |
| 3 | 3 | knowledge_defense | research | 4 | 3 |
| 4 | 4 | temple | building | 1 | 2 |
| 5 | 5 | monument | building | 1 | 2 |

> Die `row`/`col`-Werte sind kanonisch — sie werden 1:1 in die DB-Tabellen geschrieben. Das Grid-CSS liest sie als `grid-row: row + 1; grid-column: col + 1`.

> ⚠️ BALANCE CONCERN: Die Phase-2-Grid-Koordinaten für `hangar` (2,2), `pilot` (3,2), `trader` (3,1) sind vorläufige Werte nach der Umstrukturierung (Hangar von Phase 3 auf Phase 2, 2026-06-28). Phase 2 hat nun 11 Einträge statt 9 — visuelle Kollisionen und Pfeil-Überschneidungen müssen nach Implementierung im Techtree-Screen geprüft und ggf. korrigiert werden. Gleiches gilt für Phase 3 (securityHub/strategist neu, alte Positionen von hangar/pilot frei).

**Implementierungshinweise (Blade/JS):**

Die bisherigen 4 getrennten `<section>`-Blöcke mit je eigenem `<div class="tech-grid">` werden zu einem einzigen gemeinsamen `<div class="tech-grid">` zusammengeführt. Kategorie-Toggle-Buttons steuern `display: none` auf den einzelnen Tech-Cards (per CSS-Klasse oder `x-show` auf Card-Ebene), nicht auf Grid-Container-Ebene. Section-Titel (Gebäude / Kenntnisse / Schiffe / Berater) bleiben als positionierte Label-Elemente im Grid erhalten.

> ⚠️ BALANCE CONCERN: Die Kenntnisse `cartography` (row 7) und `defense` (row 8) liegen visuell weit unter ihrem sekundären Prereq Hangar (row 3). Das ist unvermeidbar bei 7 Kenntnissen in einer Spalte ohne Kollisionen. Falls die Pfeil-Länge als störend empfunden wird, kann `cartography` auf col 5 row 4 verschoben werden (neben drone, dem anderen Hangar-Lv1-Kind) — das würde die Kenntnisse-Spalte jedoch aufreißen und die visuelle Gruppierung schwächen.

---

