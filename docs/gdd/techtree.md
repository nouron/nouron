# Techtree

> Ausgelagert aus [`docs/GDD.md`](../GDD.md) am 2026-08-02. Rolle des Techtrees (Übersicht + Forschung), Entitäten-Übersicht, Abhängigkeitsregeln, Grid-Layout, Detailpanel und Gebäude-Aktionen im Tile-Panel inkl. Rückbau.
>
> Kapitelnummerierung und `§`-Verweise beziehen sich weiterhin auf das GDD.

---

## 11. Techtree

Der Techtree ist die **Freischalt- und Abhängigkeitsübersicht** aller Entitäten einer Kolonie: Gebäude, Kenntnisse, Schiffe und Berater. Er ist kein linearer Forschungsbaum, sondern ein **überschaubares Abhängigkeitsgitter** — die Kommandozentrale (CC) ist das einzige globale Gate, das den Fortschritt reguliert.

Der Techtree hat genau zwei Aufgaben (Owner-Entscheidung A43, 2026-09-24):

1. **Übersicht:** Was gibt es, was ist freigeschaltet, was fehlt noch, und wie hängt alles zusammen?
2. **Forschung:** Er ist der einzige Ort, an dem Kenntnisse erforscht werden (AP investieren, Stufe abschließen).

Er ist **keine Verwaltungsansicht für Gebäude**. Jede Gebäude-Aktion — Errichten, Ausbauen, Reparieren, Rückbau — findet ausschließlich im Tile-Panel der Kolonieansicht statt (§11.4). Für Schiffe und Berater gilt dasselbe Prinzip: Der Techtree zeigt Voraussetzungen und Stand, gehandelt wird im Hangar-Screen (§8b) bzw. im Berater-Screen (§13).

**Warum eine Stelle je Aktion:** Solange Gebäude an zwei Orten ausbaubar waren, liefen zwei Codepfade mit eigener Rechnung für Supply-Gate, Rabatte und Rundung nebeneinander, und der Spieler musste lernen, dass dieselbe Aktion an zwei Orten verschieden aussehen kann. Ein Gebäude ist ein Ort auf der Karte. Also passiert alles, was diesen Ort verändert, auch auf der Karte. Der Techtree beantwortet die Planungsfrage („Was kommt als Nächstes, und was brauche ich dafür?"), die Kolonieansicht die Handlungsfrage („Was tue ich jetzt mit diesem Tile?").

Das Designziel: Ein Spieler soll in 30 Sekunden verstehen, was er bauen kann und warum etwas noch gesperrt ist. Kein Micromanagement, keine Forschungsketten die Monate dauern.

---

### 11.1 Entitäten-Übersicht

Die folgende Tabelle listet alle Entitäten im Techtree.

#### Gebäude

Grid-Koordinaten (phasen-lokal) siehe §11.3.

| Key (intern) | Name (DE) | Voraussetzung | Max-Level |
|---|---|---|---|
| `commandCenter` | Kommandozentrale | — | 5 |
| `housingComplex` | Wohnhabitat | CC Lv 1 | Lv 3 je Instanz, max. 6 Instanzen |
| `harvester` | Harvester | CC Lv 1 | Lv 1, max. 2 Instanzen |
| `bioFacility` | Agrardom | CC Lv 1 + Harvester Lv 1 (Pflicht vor CC Lv 2) | max. Lv 3 |
| `sciencelab` | Analytik-Labor | CC Lv 2 (Pfadwahl) | max. Lv 5 |
| `bar` | Cantina | CC Lv 2 (Pfadwahl) | max. Lv 3 |
| `infirmary` | Krankenstation | CC Lv 2 | max. Lv 3 |
| `hangar` | Hangar | CC Lv 2 (Pfadwahl) | max. Lv 3, Instanzen ungedeckelt |
| `securityHub` | Sicherheits-Hub | CC Lv 3 | max. Lv 3 |
| `uplinkStation` | Uplink-Station | CC Lv 2 | max. Lv 3 |
| `temple` | Religiöse Stätte | CC Lv 4 | Lv 1 |
| `tradingPost` | Handelsposten | CC Lv 4 | max. Lv 3 |
| `monument` | Kolonialdenkmal | CC Lv 5 | Lv 1 |

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

> Es gibt keinen `strategist`-Berater im Techtree (Stratege zurückgestellt, §13 „Die vier Berater-Typen"). Die Spalte „AP-Typ" bezeichnet seit der AP-Zusammenlegung (§13.1) die **Domäne** des Beraters, nicht mehr einen eigenen Pool.

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
| 3 | 3 | drone | ship | 2 | 1 |
| 3 | 3 | freighter | ship | 2 | 2 |
| 2 | 2 | knowledge_geology | research | 3 | 1 |
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

### 11.4 Detailpanel: was der Techtree je Entität zeigt

Ein Klick auf eine Kachel öffnet das Detailpanel. Sein Inhalt hängt vom Entitätstyp ab.

**Kenntnisse** — voll bedienbar. Beschreibung, Effekt je Stufe, Voraussetzungen (Gebäude, CC-Stufen-Cap nach Regel 3), Fortschritt der laufenden Stufe und die Forschungsaktion selbst (AP investieren, Stufe abschließen). Das ist die einzige Stelle im Spiel, an der geforscht wird.

**Gebäude** — nur lesend, mit Sprung in die Kolonie:

- **Info:** Beschreibung, Effekt, Kosten der nächsten Stufe als Orientierung.
- **Voraussetzungen:** was erfüllt ist und was fehlt (CC-Stufe, funktionale Abhängigkeit nach Regel 2, Pfadwahl-Gate, Supply).
- **Instanzen:** eine Zeile „Anzahl / maximale Instanzen" (z. B. Wohnhabitat), bei Gebäuden ohne Instanz-Deckel nur die Anzahl (Hangar). Der Deckel ist `max_instances`, nicht `max_level` — die beiden Achsen (§4c) dürfen in der Anzeige nicht verwechselt werden.
- **Instanzliste:** eine Zeile je platzierter Instanz mit Stufe (bezogen auf `max_level`) und Zustand (Status-Punkte bzw. Zustandsklasse wie im Tile-Panel). Jede Zeile hat den Link **„Zum Tile"**. Er öffnet die Kolonieansicht mit genau dieser Instanz ausgewählt (Deep-Link mit Gebäude und Instanz als Parameter).
- **„In der Kolonie errichten":** sichtbar, solange eine weitere Instanz möglich ist, d. h. der Instanz-Deckel nicht erreicht ist bzw. bei einem Gebäude ohne Instanzen noch keine steht. Der Link führt in die Kolonieansicht mit vorgewähltem Gebäude, denselben Einstieg, den auch die Bau-Hints nutzen (§16.2). Ob die Platzierung dort gelingt (Regolith, Supply, freies Tile), prüft die Kolonieansicht. Der Techtree zeigt fehlende Voraussetzungen vorab an, sperrt den Link aber nicht, damit der Spieler auch zum Planen in die Karte springen kann.

Der Techtree zeigt bei Gebäuden **keine** Buttons für Ausbauen, Reparieren oder Rückbau.

**Schiffe** — nur lesend: Voraussetzung (Hangar-Stufe), Einsatzzweck, Verweis auf den Hangar-Screen.

**Berater** — nur lesend: Slot-Gate, Domäne, Verweis auf den Berater-Screen.

---

### 11.5 Gebäude-Aktionen im Tile-Panel (Kolonieansicht)

Alle Aktionen an einem Gebäude laufen über das Tile-Panel des Tiles, auf dem es steht. Je Aktion gibt es genau eine Stelle.

| Aktion | Wirkung | Kosten / Gate |
|---|---|---|
| **Errichten** | Gebäude auf ein freies, passendes Tile setzen (Stufe 0, Baustelle) | Regolith bzw. Werkstoffe, AP; Supply-Gate beim Platzieren (§6 „Supply als Bau-Gate"); Zonen-Regel (§4 „Bauregeln") |
| **Ausbauen** | AP in die nächste Stufe investieren, bei Erreichen der Schwelle Stufe +1 | AP, Regolith bei Abschluss (§4 „Baukosten & Level-Up-Kosten"); Supply-Gate ab der zweiten Stufe |
| **Reparieren** | Status-Punkte zurückholen | AP + Regolith je Schritt (CC und Harvester nur AP) |
| **Rückbau** | Stufe der Instanz um eins senken; bei einer Baustelle auf Stufe 0: Bauabbruch | kostenlos, ohne Voraussetzungen, Bestätigung per Modal-Dialog (siehe unten) |

Die Kosten zeigt das Tile-Panel wie jede AP-Aktion als Chip am Button. Die angezeigte Zahl ist die wirkende Zahl, inklusive Rabatte.

#### Rückbau (Spieleraktion)

Der Direktor kann jede Instanz eines Gebäudes bewusst um eine Stufe zurückbauen (Owner-Entscheidung A14/A43, 2026-09-24). Der Rückbau ist das Gegenstück zum Verfall: Verfall nimmt Stufen durch Vernachlässigung (§7), Rückbau nimmt sie durch Entscheidung.

**Regeln:**

- **Eine Stufe je Aktion, je Instanz.** Der Rückbau senkt die Stufe der gewählten Instanz um genau eins. Andere Instanzen desselben Gebäudes bleiben unberührt.
- **Kostenlos und ohne Voraussetzungen.** Keine AP, keine Ressourcen, kein Supply-Gate, keine Levelup-Voraussetzungen. Es gibt auch keine Rückerstattung. Das Material ist verbaut, der Rückbau gibt nichts zurück.
- **Die Kommandozentrale fällt nie unter Stufe 1.** Sie ist der Anker der Kolonie und kann nicht geräumt werden.
- **Rückbau auf Stufe 0 räumt das Tile.** Das Gebäude ist danach nicht mehr platziert: Das Tile ist frei, und die Arbeitsplatz-Reserve der ersten Stufe entfällt (§6 „Supply als Bau-Gate"). Wer es wieder haben will, muss es neu errichten, mit vollen Kosten.
- **Bauabbruch.** Der Rückbau gilt auch für eine platzierte Baustelle auf Stufe 0. Das Tile und die Arbeitsplatz-Reserve werden frei. Es gibt keine Rückerstattung der Errichtungskosten, und bereits investierte AP verfallen. Einen eigenen Begriff oder eine eigene Aktion braucht das nicht: Es ist dieselbe Regel wie beim Rückbau auf Stufe 0. (Im Code noch umzusetzen, dort verlangt der Rückbau derzeit eine Stufe größer 0.)
- **Investierte AP eines laufenden Ausbaus verfallen.** Steckt in der Instanz bereits AP für die nächste Stufe, sind diese AP mit dem Rückbau weg.
- **Zustand:** Die verbleibende Stufe steht danach mit vollem Zustand da, genauso wie nach einem Stufenverlust durch Verfall (§7).
- **Arbeitsplätze sinken mit der Stufe.** Weniger Stufen binden weniger Kolonisten. Freies Supply entsteht sofort (§6 „Supply als Bau-Gate"). Sind Arbeitsplätze unbesetzt, verschwinden sie still mit, nach denselben Regeln wie beim Stufenverlust durch Verfall (§6 „Überkapazität — Konsequenzen", Absatz „Abgang von Arbeitsplätzen").
- **Wohnraum:** Der Rückbau eines Wohnhabitats senkt die Kolonisten-Kapazität. Liegt die Kolonie danach über ihrem Cap, entsteht Überkapazität mit Frist, Vertrauens-Malus und Abwanderung (§6). Damit ist der Rückbau neben Verfall und Sturm der dritte Weg, Wohnraum zu verlieren, und der einzige, den der Spieler selbst wählt.
- **Effekte skalieren mit der neuen Stufe**, wie nach jedem Stufenverlust. Vertrauensbeiträge, Produktion, Rabatte und Supply-Cap-Beiträge sinken automatisch mit.
- **Kein Vertrauens-Ereignis.** Der Rückbau ist eine bewusste Verwaltungsentscheidung des Direktors, keine Vernachlässigung, und löst kein eigenes Vertrauens-Ereignis aus. Die Folgen stecken schon im System: Vertrauensgebäude verlieren ihren Stufenbeitrag, und ein Wohnhabitat-Rückbau kann Überkapazität mit eigenem Malus auslösen (§6). Das Config-Ereignis `trust.events.building_level_down` wird derzeit nirgends ausgelöst (ROADMAP T13). Falls T13 es aktiviert, feuert es nur beim Stufenverlust durch Verfall, nie beim Rückbau.
- **Bestandsschutz.** Unterschreitet ein Rückbau eine Voraussetzung, auf der schon etwas steht, bleibt das Bestehende erhalten: Gebäude, angestellte Berater, Schiffe und erreichte Kenntnis-Stufen. Nur neue Aktionen werden gegen die neue Stufe geprüft. Beispiele sind eine zurückgebaute CC trotz bebauter Kolonie-Zone oder belegter Berater-Slots, ein Pfadgebäude auf Stufe 0 bei angestelltem Berater und ein Analytik-Labor unter der Voraussetzung erforschter Kenntnisse. Ausgenommen ist nur, was die Regeln schon heute am aktuellen Zustand festmachen: Ein Hangar unter der Stufe seines Schiffs deaktiviert das Schiff, bis er wieder ausgebaut ist (§7).
- **Bestätigung per Modal-Dialog.** Der Rückbau kostet nichts, ist aber nicht umkehrbar. Ein Fehlklick kostet eine Stufe, also AP und Regolith für den Wiederaufbau. Deshalb bestätigt der Spieler jeden Rückbau in einem Modal-Dialog. Der Dialog ist kein bloßes „Sicher?", er zeigt die konkreten Folgen:
  - die neue Stufe,
  - verfallende investierte AP (falls vorhanden),
  - frei werdende Kolonisten,
  - bei Wohnhabitaten den Kapazitätsverlust und ob dadurch Kolonisten obdachlos werden,
  - bei Rückbau auf Stufe 0 bzw. Bauabbruch, dass das Tile geräumt wird.

  Warnfälle werden hervorgehoben: Stufe 0 bzw. Bauabbruch, verfallende AP und drohende Überkapazität. Die angezeigten Folgen sind die wirkenden Folgen.

**Wofür der Rückbau da ist:** Der Rückbau ist ein Werkzeug zum Umplanen, nicht zum Sparen. Er macht Bauplatz frei (ein Gebäude auf Stufe 0 räumen, um das Tile anders zu nutzen), er senkt die Instandhaltungslast (§13.5) und er setzt Kolonisten frei, wenn Supply gebraucht wird. Weil er nichts zurückgibt, ist er nie ein Gewinngeschäft. Er tauscht Investition gegen Spielraum. Das passt zu „Entscheidungen ohne Optimalpfad": Ein Gebäude abzureißen, das man teuer gebaut hat, soll sich wie eine echte Abwägung anfühlen.

> ⚠️ BALANCE CONCERN: Weil die verbleibende Stufe mit vollem Zustand dasteht, ist ein Rückbau bei fast verfallenem Gebäude ein kostenloser Ersatz für die Reparatur, mit Stufenverlust. Das Ergebnis ist identisch mit dem, was der Verfall ohnehin gleich tun würde. Der Rückbau zieht den Verlust also nur vor, er schafft keinen neuen Vorteil. Relevant wird das erst, wenn der Stufenverlust durch Verfall eine zusätzliche Folge bekommt, die der Rückbau nicht hat (`building_level_down`, falls T13 es aktiviert; Recycling des Sicherheits-Hubs, §4). Dann wird „kurz vor dem Verfall selbst zurückbauen" zur Ausweichtaktik. Da der Rückbau bewusst kein Vertrauens-Ereignis auslöst (siehe Regeln oben), ist diese Ausweichtaktik akzeptiert: Wer den Stufenverlust aktiv vorzieht, handelt nicht nachlässig. Nach einer Aktivierung von T13 prüfen, ob sie im Playtest zur Routine wird.

> ⚠️ BALANCE CONCERN: Harvester (`max_level` 1) — jeder Rückbau ist hier ein Rückbau auf Stufe 0 und räumt das Tile. Zu prüfen ist, ob „zurückbauen und woanders neu errichten" billiger oder schneller ist als das reguläre Verlegen (AP je Hex plus ein Sol Stillstand, §4). Dann würde die Verlege-Mechanik umgangen. Ebenso offen: ob eine zurückgebaute zweite Harvester-Instanz neu errichtet werden darf, obwohl ihre Bezugsquelle (Orin bzw. Bergungsmission, §4c) schon verbraucht ist.
---

