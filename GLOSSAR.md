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
| Analytik-Labor („Labor“) | `sciencelab` (ID 31) | Pfadgebäude ohne eigene Kenntnis und kein Tor zu Kenntnissen; bringt Tempo und Genauigkeit (Analytiker, Rabatt auf Kenntnis-Stufen, genaue Zahlen). Im Code und in Berichten nie „Sciencelab“ neben „Labor“ als zwei Dinge lesen. |
| Hangar | `hangar` | Pfadgebäude; Schiffe, Missionen, Bergung. |
| Cantina | `bar` | Pfadgebäude; Begegnungen, Handel, Anliegen. |
| Cantina-Besetzung | `cantina_casts` | Die Figuren, die an einem Sol neben dem Barkeeper in der Cantina sitzen. |
| Uplink-Station | `uplinkStation` | Pflichtgebäude in Phase 1; Tor zum Werkstoff-Import. |
| Leitstelle | `securityHub` | Sicherheit/Bergungsdienst (Entwurf). |
| Krankenstation | `infirmary` | Gesundheit der Kolonie. |
| Handelsposten | `tradingPost` | Handelskonditionen (spätere Phase). |
| Religiöse Stätte / Kolonialdenkmal | `temple` / `monument` | Späte Gebäude. |
| **Pfadgebäude** | – | Interner Begriff (Spielerbegriff: **Fokus-Gebäude**, Marke je Gebäude: Kenntnisse, Missionen, Beziehungen; Texte nie „Pfad“ oder „Schwerpunkt“ nennen). Eines der drei Gebäude Labor, Hangar, Cantina. Das erste kommt in Phase 1, das zweite und dritte ab CC Lv3 bzw. Lv4 (Pfadwahl-Gate). |
| **Vertiefung** | – | Gemeinsame Bauart der Pfadgebäude: mehrere Dinge (Kenntnis, Figur, Missionstyp), jedes in fünf Stufen allein mit AP aus dem gemeinsamen Pool ausbaubar (Ausbau), unabhängig von der Ausführung; nur ein Strukturmodell, nirgends als Raster zu sehen *(Entwurf)*. |
| **Vertrautheit** | – | Bindung an eine Cantina-Figur (Gesprächslevel); steigt per AP-Investition (Beziehung pflegen), erst nach dem ersten Treffen der Figur im Lauf. Nicht zu verwechseln mit Vertrauen (Kolonie-Stimmung) *(Entwurf)*. |
| **Beziehungsstufe** | `colony_path_levels` (`character`) | Stufe der Vertrautheit mit einer Figur, im Spiel als Wort: fremd, neu kennengelernt, bekannt, vertraut, befreundet, brüderlich; bei Tomas bestimmt sie, wie oft er Rat gibt (jeder Rat gleich groß). |
| **Gespräch** | `conversation_options` | Cantina-Investition in die Vertrautheit: ein Gespräch je Figur und Sol in wählbarer Länge (kurz oder lang), voller Preis, Rest über der nächsten Beziehungsstufe verfällt mit Warnung. |
| **Missionserfahrung** | – | Stufe der Kolonie in einer Missionsfamilie; steigt per AP-Investition als Training im Simulator, ohne Einsatz (Vorschlag für das Label, Alternative „Training“; ersetzt den früheren Arbeitsbegriff „Routine“) *(Entwurf)*. |
| **Pflichtgebäude (Phase 1)** | – | Was Phase 1 abschließt: CC Lv2, Harvester, Wohnhabitat, Agrardom, Uplink-Station, ein Pfadgebäude (je Lv1) und zwei Berater. |

## Ressourcen und Action Points

| Spielbegriff | Code-Schlüssel | Bedeutung |
|---|---|---|
| Credits (Cr) | `credits` | Geld: Berater, Schiffe, Handel. |
| Kolonisten (KOL) | `supply` | **Kein Zuzug, kein Wachstum.** Kolonisten = Wohnraum aus CC und Wohnhabitaten (sofort bei Abschluss und bei Verlust); davon beschäftigt = besetzte Arbeitsplätze, der Rest ist frei. Unbesetzte Arbeitsplätze mindern die Rohstoffproduktion. |
| Regolith (Rg) | `regolith` | Baumaterial; Baukosten werden beim Platzieren voll gezahlt. |
| Regolith-Vorkommen | `regolith_y{Ertrag}_d{Vorkommen}` | Feld mit Regolith; Ertrag (niedrig/mittel/hoch) bestimmt die Förderung je Sol, Vorkommen (klein/mittel/groß) die Gesamtmenge. |
| Werkstoffe (Co) | `compounds`/`werkstoffe` | Material für spätere Gebäude, per Uplink-Station importierbar. |
| Organika (Or) | `organics`/`organika` | Nahrung der Kolonisten; Proviant für Missionen. Zielbild: 1 Einheit ernährt 1 Kolonisten pro Sol (ROADMAP T36). |
| Vertrauen | `trust` (früher `moral`) | Stimmung der Kolonie, jeden Sol neu als Grundwert plus Quellen (Gebäude, Kenntnisse, Frachter, Ereignisse, Hunger) berechnet; wirkt nur über ihre Stufe auf die AP, als Zahl erst mit Analytik-Labor sichtbar. |
| Vertrauensstufe | `trust.bands` | Eine von fünf Stufen des Vertrauens (Aufruhr, Unruhig, Stabil, Zufrieden, Euphorisch), jede mit festem AP-Zuschlag oder -Abzug; der Spieler sieht Wort und Wirkung statt der Zahl. |
| Action Points (AP) | `ap` | Ein gemeinsamer Pool für die ganze Kolonie (Grundwert plus Berater). |
| **Idle-AP** | – | AP, die am Sol-Ende ungenutzt verfallen. Messgröße, kein Spielbegriff. |

## Berater (`advisor`)

| Spielbegriff | Code-Schlüssel | Bedeutung |
|---|---|---|
| Baumeister | `engineer` | Fester erster Berater. Zahlt AP ein; sein Fach-Deckel hebt den Bau-Deckel. |
| Analytiker | `scientist` | Forschung; kommt mit dem Labor, sein Fach-Deckel hebt den Forschungs-Deckel jeder Kenntnis. |
| Raumfahrer | `pilot` | Erkundung und Flüge; kommt mit dem Hangar, sein Fach-Deckel hebt den Training-Deckel. |
| Konsul | `trader` | Handel; kommt mit der Cantina. |
| Stratege | `strategist` | Zurückgestellt. |
| Rang | `rank` | Junior ist Rang 1; jeder Rang erhöht genau eine Zahl, Pool-AP oder Fach-Deckel. |

## Zeit und Phasen

| Spielbegriff | Code-Schlüssel | Bedeutung |
|---|---|---|
| Sol | `tick` | Eine Spielrunde. Im Code heißt sie weiter „tick“. |
| Run | `run` | Eine Partie mit Ziel und klarem Ende; genau ein aktiver Run. |
| Auftrag | – | Was Nexus von der Kolonie in einem Run verlangt; der Run endet mit „Auftrag erfüllt“ oder „Auftrag gescheitert“. Im Menü heißt die Partie weiter „Run“. |
| Mission | `mission` | Ein Einsatz eines Hangar-Schiffs (Erkundung, Bergung, Handel …), nie der ganze Run. |
| Aufgabe (Run-Ziel) | `run_objectives` / `run.tasks` | Eine der drei Nexus-Aufgaben, die beim Übergang in Phase 2 gezogen werden; zwei davon erfüllt heißt Sieg. |
| Zeithorizont | `horizon` (`medium`/`long`) | Klasse einer Aufgabe: mittelfristig misst eine Tätigkeit oder ein Einkommen, langfristig den Reifegrad der ganzen Kolonie; jede Ziehung enthält beide *(Entwurf)*. |
| Fokus-Aufgabe | – | Mittelfristige Aufgabe, die ein Fokus-Gebäude füttert: Labor → Forschungsvorsprung, Hangar → Expeditionen, Cantina → Handelspartner *(Entwurf)*. |
| Forschungsvorsprung | `task_research_lead` | Aufgabe: Stufen aller Kenntnisse zusammen über einer Schwelle. |
| Expertenstab | `task_senior_advisors` | Aufgabe: Ränge aller Berater der Kolonie zusammen über einer Schwelle. |
| Selbstversorgung | `task_self_sufficiency` | Aufgabe: Die eigene Agrardom-Ernte deckt viele Sole in Folge den Bedarf einer Kolonie ab Mindestgröße, ohne Hunger; Vorrat zählt nicht *(Entwurf)*. |
| Phase 1 „Grundaufbau“ | `phase` 1 | Endet mit den Pflichtgebäuden; harte Frist, sonst Run verloren (`phase1_deadline_sol`). |
| Phase 2 | `phase` 2 | Beginnt mit dem Abschluss von Phase 1; zweites und drittes Pfadgebäude, CC-Ausbau. |

## Mechaniken

| Spielbegriff | Code-Schlüssel | Bedeutung |
|---|---|---|
| Ausbaustufe | `level` | Stufe eines Gebäudes. „Level“ ist nur der Code-Name. |
| Ausbauen / Baustelle | `invest` | AP in eine Ausbaustufe investieren; ein begonnener Ausbau heißt Baustelle (`ap_spend`). |
| Stufen-Gate | `building_cc_gate` | Eine Ausbaustufe braucht mindestens gleich hohe Kommandozentrale. |
| Pfadwahl-Gate | `path_building_gate` | Platzierte Pfadgebäude müssen kleiner sein als CC-Stufe minus 1. |
| **AP-Deckel** | `ap_cap` | Ein Ziel (Gebäude, Kenntnis, Missionsfamilie, Figur) nimmt pro Sol nur begrenzt AP auf: Grundwert plus Fach-Deckel des passenden Beraters. Zeit-Bremse ohne Eingriff ins Regolith-Einkommen. Reparieren, Platzieren und Belohnungs-AP sind ausgenommen. |
| **Fach-Deckel** | `cap_bonus_per_rank` | Rang-Beitrag eines Fach-Beraters zum AP-Deckel seines Fachs (Baumeister → Bau, Analytiker → Forschung, Raumfahrer → Training), getrennt von seinem Beitrag zum Pool. |
| Supply-Kappe | `supply_cap` | Obergrenze der Kolonisten: CC fest plus Wohnhabitat-Stufen. |
| Kenntnis | `knowledge`/`research` | Erforschbarer Effekt, freigeschaltet von genau einem eigenen Gebäude, das sie verstärkt (z. B. Geologie am Harvester); „Forschung“ ist die Tätigkeit, „Kenntnis“ das Ergebnis. |
| Grundkenntnis | – | Kenntnis an der Kommandozentrale oder einem Fokus-Gebäude (Konstruktion, Kartografie, Handel). |
| Sockel-Kenntnis | – | Kenntnis an einem Produktionsgebäude aus Phase 1, offen für jede Eröffnung (Geologie, Agronomie). |
| Fortgeschrittene Kenntnis | – | Kenntnis an einem späteren Gebäude (Gesundheit an der Krankenstation, Verteidigung an der Leitstelle). |
| Labor-Vorschau | `preview` | Der Techtree („Labor“ im Menü) ohne Analytik-Labor: erforschbar, sobald das Gebäude der Kenntnis steht, aber Fortschritt nur grob in Worten und ohne Zahlen; erreichte Stufen und ihr Effekt genau. |
| **Stille Forschung** | `investBonus` | Zweckgebundene Bonus-AP aus der Cantina (Tomas, Sarka, Deva, Lenn) lassen eine Kenntnis von selbst aufsteigen; es gelten dieselben Sperren wie für eigene Forschung. |
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
