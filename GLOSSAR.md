# Glossar

Eine Sache, ein Name. Die Spalte **Spielbegriff** ist der Name im UI und in allen Berichten an den Owner; der **Code-Schlüssel** steht in Config, Lang und Datenbank. Bedeutungen statt Zahlen (ADR 0004: Werte stehen in `config/*.php` und `docs/game-reference.md`).

**Regeln:** Berichte nennen den Spielbegriff, den Code-Schlüssel höchstens einmal in Klammern. Neue Entität oder Mechanik bekommt hier einen Eintrag (eine Zeile, ein Satz). Einträge mit *(Entwurf)* sind noch vom Owner zu prüfen.

## Gebäude

| Spielbegriff | Code-Schlüssel | Bedeutung |
|---|---|---|
| Kommandozentrale (CC) | `commandCenter` | Taktgeber des Aufbaus. Ihre Stufe gibt die Phasen vor und begrenzt die Stufen aller anderen Gebäude (Stufen-Gate). |
| Wohnhabitat | `housingComplex` | Schafft Platz für Kolonisten (Supply-Kappe). Mehrere Instanzen möglich. Stufe 1 ist beim Start schon da. |
| Harvester | `harvester` | Fördert Regolith auf seinem Tile. Es gibt nur einen; er wird in Sol 0 einmal verlegt. Die zweite Instanz ist vorerst gestrichen. |
| Agrardom | `bioFacility` (ID 41) | Produziert Organika. Pflichtgebäude vor CC Lv2, gehört nicht zur Pfadwahl. |
| Analytik-Labor („Labor“) | `sciencelab` (ID 31) | Pfadgebäude; mit Stufe 1 lassen sich Kenntnisse erforschen. Im Code und in Berichten nie „Sciencelab“ neben „Labor“ als zwei Dinge lesen. |
| Hangar | `hangar` | Pfadgebäude; Schiffe, Missionen, Bergung. |
| Cantina | `bar` | Pfadgebäude; Begegnungen, Handel, Anliegen. |
| Uplink-Station | `uplinkStation` | Pflichtgebäude in Phase 1; Tor zum Werkstoff-Import. |
| Leitstelle | `securityHub` | Sicherheit/Bergungsdienst (Entwurf). |
| Krankenstation | `infirmary` | Gesundheit der Kolonie. |
| Handelsposten | `tradingPost` | Handelskonditionen (spätere Phase). |
| Religiöse Stätte / Kolonialdenkmal | `temple` / `monument` | Späte Gebäude. |
| **Pfadgebäude** | – | Eines der drei Gebäude Labor, Hangar, Cantina. Das erste kommt in Phase 1, das zweite und dritte ab CC Lv3 bzw. Lv4 (Pfadwahl-Gate). |
| **Vertiefung** | – | Gemeinsame Bauart der Pfadgebäude: mehrere Dinge (Kenntnis, Figur, Missionstyp), jedes in fünf Stufen allein mit AP aus dem gemeinsamen Pool ausbaubar (Ausbau), unabhängig von der Ausführung; nur ein Strukturmodell, nirgends als Raster zu sehen *(Entwurf)*. |
| **Vertrautheit** | – | Bindung an eine Cantina-Figur (Gesprächslevel); steigt per AP-Investition (Beziehung pflegen), erst nach dem ersten Treffen der Figur im Lauf. Nicht zu verwechseln mit Vertrauen (Kolonie-Stimmung) *(Entwurf)*. |
| **Gespräch** | – | Cantina-Investition als festes AP-Paket je Figur; reine AP-Investition in die Vertrautheit, ohne Zusatzertrag *(Entwurf)*. |
| **Missionserfahrung** | – | Stufe der Kolonie in einer Missionsfamilie; steigt per AP-Investition als Training im Simulator, ohne Einsatz (Vorschlag für das Label, Alternative „Training“; ersetzt den früheren Arbeitsbegriff „Routine“) *(Entwurf)*. |
| **Pflichtgebäude (Phase 1)** | – | Was Phase 1 abschließt: CC Lv2, Harvester, Wohnhabitat, Agrardom, Uplink-Station, ein Pfadgebäude (je Lv1) und zwei Berater. |

## Ressourcen und Action Points

| Spielbegriff | Code-Schlüssel | Bedeutung |
|---|---|---|
| Credits (Cr) | `credits` | Geld: Berater, Schiffe, Handel. |
| Kolonisten (KOL) | `supply` | **Kein eigenes Bevölkerungssystem.** 1 Kolonist = 1 belegter Supply-Platz; die Kappe kommt aus CC und Wohnhabitaten. |
| Regolith (Rg) | `regolith` | Baumaterial; Baukosten werden beim Platzieren voll gezahlt. |
| Werkstoffe (Co) | `compounds`/`werkstoffe` | Material für spätere Gebäude, per Uplink-Station importierbar. |
| Organika (Or) | `organics`/`organika` | Nahrung der Kolonisten; Proviant für Missionen. Zielbild: 1 Einheit ernährt 1 Kolonisten pro Sol (ROADMAP T36). |
| Vertrauen | `trust` (früher `moral`) | Stimmung der Kolonie; Hunger senkt sie, ebenso Unterbesetzung *(Entwurf, nicht geprüft)*. |
| Action Points (AP) | `ap` | Ein gemeinsamer Pool für die ganze Kolonie (Grundwert plus Berater). |
| **Idle-AP** | – | AP, die am Sol-Ende ungenutzt verfallen. Messgröße, kein Spielbegriff. |

## Berater (`advisor`)

| Spielbegriff | Code-Schlüssel | Bedeutung |
|---|---|---|
| Baumeister | `engineer` | Fester erster Berater. Zahlt AP ein; sein Rang hebt den AP-Deckel. |
| Analytiker | `scientist` | Forschung; kommt mit dem Labor. |
| Raumfahrer | `pilot` | Erkundung und Flüge; kommt mit dem Hangar. |
| Konsul | `trader` | Handel; kommt mit der Cantina. |
| Stratege | `strategist` | Zurückgestellt. |
| Rang | `rank` | Junior ist Rang 1; höhere Ränge geben mehr AP und mehr Deckel-Bonus. |

## Zeit und Phasen

| Spielbegriff | Code-Schlüssel | Bedeutung |
|---|---|---|
| Sol | `tick` | Eine Spielrunde. Im Code heißt sie weiter „tick“. |
| Run | `run` | Eine Partie mit Ziel und klarem Ende; genau ein aktiver Run. |
| Phase 1 „Grundaufbau“ | `phase` 1 | Endet mit den Pflichtgebäuden; harte Frist, sonst Run verloren (`phase1_deadline_sol`). |
| Phase 2 | `phase` 2 | Beginnt mit dem Abschluss von Phase 1; zweites und drittes Pfadgebäude, CC-Ausbau. |

## Mechaniken

| Spielbegriff | Code-Schlüssel | Bedeutung |
|---|---|---|
| Ausbaustufe | `level` | Stufe eines Gebäudes. „Level“ ist nur der Code-Name. |
| Ausbauen / Baustelle | `invest` | AP in eine Ausbaustufe investieren; ein begonnener Ausbau heißt Baustelle (`ap_spend`). |
| Stufen-Gate | `building_cc_gate` | Eine Ausbaustufe braucht mindestens gleich hohe Kommandozentrale. |
| Pfadwahl-Gate | `path_building_gate` | Platzierte Pfadgebäude müssen kleiner sein als CC-Stufe minus 1. |
| **AP-Deckel** | `ap_cap` | Ein Ziel (Gebäude) nimmt pro Sol nur begrenzt Ausbau-AP auf: Grundwert plus Beitrag des passenden Beraters. Zeit-Bremse ohne Eingriff ins Regolith-Einkommen. Reparieren, Platzieren und Belohnungs-AP sind ausgenommen. |
| Supply-Kappe | `supply_cap` | Obergrenze der Kolonisten: CC fest plus Wohnhabitat-Stufen. |
| Kenntnis | `knowledge`/`research` | Erforschbarer Effekt (z. B. Baukunde, Kartographie, Geologie). „Forschung“ ist die Tätigkeit, „Kenntnis“ das Ergebnis. |
| Hunger | `hunger_streak` | Organika reicht nicht; Vertrauens-Malus, der mit jeder Folge-Runde wächst. |
| Signal / Tiefenscan / Bergung | – | Verstecktes Signal auf einem Feld, Scan zeigt den Inhalt, Bergung holt ihn. |
| Nexus | `nexus_debt` | Die Fraktion hinter Subvention, Kredit und Schulden; „Nexus-DB“ im UI ist dagegen die Enzyklopädie. |

## Mess- und Balancebegriffe (intern)

| Begriff | Bedeutung |
|---|---|
| Eröffnung | Labor-First, Hangar-First oder Cantina-First: welches Pfadgebäude zuerst steht. Soll gleichwertig sein. |
| Minimum-Spieler / Bot | Spielt nur die Pflichtgebäude (PlaytestBot, Engine `utility`). Der Bot ist **kein Mensch**; sein Ergebnis ist ein Messwert, kein Urteil über das Spielgefühl. |
| Rg-Puffer | Regolith, das bis zum Ende von Phase 1 über dem Minimum liegt. Platz für Extras. |
| Zielranking | Reihenfolge, in der der Bot Käufe plant. Nicht das Spielverhalten von Menschen. |
| Batch / Messung | Ein Durchlauf mehrerer Bot-Partien mit festen Seeds; eine Änderung pro Messung. |
| gemessen / gerechnet / Annahme | In Berichten gekennzeichnet: aus Reports belegt / aus Config berechnet / Deutung ohne Beleg. |

## Namens-Fallen

- **Level ↔ Ausbaustufe**, **Supply ↔ Kolonisten**, **tick ↔ Sol**, **moral ↔ Vertrauen**, **advisor ↔ Berater**.
- **Labor ↔ Sciencelab ↔ Analytik-Labor** sind dasselbe Gebäude.
- **Nexus-DB** (Enzyklopädie) ↔ **Nexus** (Schulden/Subvention).
- Es gibt **keine AP-Arten**: AP ist immer der eine gemeinsame Pool.
