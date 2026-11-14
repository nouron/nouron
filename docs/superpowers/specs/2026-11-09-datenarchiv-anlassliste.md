# Datenarchiv: Anlass- und Schwellenliste (Stand 2026-11-09)

Status: **Entwurf zur Owner-Freigabe, nichts umgesetzt, nichts gemessen, kein Bot-Batch gestartet.** Grundlage: Kasten und „Owner-Entscheidungen zur Evaluation“ in `2026-11-08-nexusdb-codex-zusammenfuehren-entwurf.md`, Evaluation `2026-11-09-datenarchiv-freischalt-evaluation.md`, `app/Services/CharacterCodexService.php`, alle Aufrufer von `recordProgress` (Cantina-Dienst, Tick, Kolonie-Controller), `config/game.php` (Bereiche `bar`, `character_codex`), `config/characters.php`. Zahlen aus eigener Rechnung sind **Annahme (gerechnet)**, Deutungen sind **Annahme**. Es gibt keine gemessenen Werte in diesem Dokument.

## 0. Begriffe und Regeln dieser Liste

- **Anlass** ist das Ereignis, das heute `recordProgress()` aufruft. **Schwelle** ist die Zahl der bereits gezählten Läufe (`runs_played`), die mindestens vorliegen muss. Im ersten Lauf ist der Zähler 0, im fünften Lauf 4.
- Ein Eintrag öffnet sich nur, wenn Anlass **und** Schwelle erfüllt sind. Ein Anlass bei fehlender Schwelle **verfällt** (Owner: kein Ansparen).
- Annahme (gerechnet aus dem Ist-Code): `recordProgress()` öffnet immer den **nächsten** Eintrag der Figur (Nummer = bisherige Zahl + 1). Ein Anlass öffnet also höchstens einen Eintrag, und die Reihenfolge der Einträge ist fest. Die Schwelle wird gegen die Nummer des nächsten Eintrags geprüft.
- Annahme: Der Lauf-Zähler steigt **am Lauf-Ende** (Ausgang egal, Lauf zählt ab Sol 30 oder bei regulärem Ende). Er ändert sich also mitten im Lauf nie. Der Owner-Text lässt offen, ob er schon bei Erreichen von Sol 30 steigt (Frage 1).
- Schwellen sind **global je Eintragsposition** (**Annahme des Owner-Assistenten, noch nicht vom Owner bestätigt**). Nur Eintrag 4 ist je Figur leicht gestreut.

## 1. Figuren: 15 Figuren mal 5 Einträge

### 1.1 Schwellen je Eintragsposition

| Eintrag | 1 | 2 | 3 | 4 | 5 |
|---|---|---|---|---|---|
| Schwelle (Läufe bereits gezählt) | 0 | 0 | 1 | **2 oder 3 (gestreut)** | 4 |
| Frühester Lauf, in dem der Eintrag möglich ist | 1 | 1 | 2 | 3 oder 4 | 5 |

Streuung bei Eintrag 4 (Annahme, Begründung): **Schwelle 3** für die Figuren, die ohnehin schnell Anlässe liefern (Aldra, Sorel, der Fremde, Zara, Voss, Deva, Lenn, Tomas; zusammen 8), **Schwelle 2** für die langsamen (Dax, Vesper, Sarka, Maret, Fen, Juno, Orin; zusammen 7). Grund: Bei schnellen Figuren bremst nur die Schwelle, bei langsamen bremst schon der Zufall der Anlässe, dort soll die Schwelle nicht zusätzlich bremsen. Wirkung: In Lauf 3 springen höchstens 7 Einträge-4 auf, in Lauf 4 höchstens 8, nie alle 15 zugleich. Eintrag 5 hat für alle dieselbe Schwelle 4, damit „alles offen“ nie vor dem fünften Lauf möglich ist.

### 1.2 Anlass je Figur (nur vorhandene Trigger)

„Rate“ ist die Zahl der Anlässe je Lauf aus Tabelle 1.2 der Evaluation (**Annahme (gerechnet)**, Cantina-Lauf, ca. 60 Cantina-Sols). Alle Einträge einer Figur nutzen denselben Anlass-Typ.

| Figur | Anlass-Typ (aus dem Code) | Spielerhandlung nötig? | Rate je Lauf (Annahme) | Schwelle Eintrag 4 | Eintrag 5 |
|---|---|---|---|---|---|
| Tomas (Barkeeper) | Cantina-Ausbaustufe fertig gebaut (Kolonie-Controller, jede fertige Ausbaustufe öffnet den nächsten Eintrag) | Ja (Bauentscheidung) | 1 bis 5, begrenzt durch erreichte Stufe | 3 | **keine Schwelle**, ausgelöst durch ein Gespräch mit Tomas (siehe 1.3) |
| Dax (Schmuggler) | Gast-Handel mit seinem Namen angenommen (Cantina-Dienst, Handelsangebot) | Ja (2 Aktionspunkte) | 1 bis 2, unsicher | 2 | Schwelle 4 |
| Vesper (Informationshändlerin) | Gast-Handel mit ihrem Namen angenommen | Ja | 1 bis 2, unsicher | 2 | Schwelle 4 |
| Sarka (Mechanikerin) | Gast-Handel mit ihrem Namen angenommen | Ja | 1 bis 2, unsicher | 2 | Schwelle 4 |
| Maret (Kolonieärztin) | Gast-Handel mit ihrem Namen angenommen | Ja | 1 bis 2, unsicher | 2 | Schwelle 4 |
| Fen (Prospektor) | Gast-Handel mit seinem Namen angenommen | Ja | 1 bis 2, unsicher | 2 | Schwelle 4 |
| Juno (Söldnerin a. D.) | Gast-Handel mit ihrem Namen angenommen | Ja | 1 bis 2, unsicher | 2 | Schwelle 4 |
| Zara (Spielerin) | Wette abgeschlossen (Begegnungs-Slot, Typ Wette) | Ja (2 Aktionspunkte, Ausgang egal) | ca. 3 | 3 | Schwelle 4 |
| Voss (Schrotthändler) | Auktion abgeschlossen (Begegnungs-Slot, Typ Auktion) | Ja | ca. 3 | 3 | Schwelle 4 |
| Deva (Veteranin) | Informations-Begegnung gelöst (eigener Zufallskanal, jede Art von Ausgang zählt) | Ja | ca. 2 bis 3 | 3 | Schwelle 4 |
| Lenn (KI-Forscher) | Informations-Begegnung gelöst | Ja | ca. 2 bis 3 | 3 | Schwelle 4 |
| Aldra (Gründerin) | Tageswurf der Story-Begegnung fällt auf sie (Tick, ohne Spielerhandlung) **oder** ihr Anliegen gelöst (Cantina-Dienst) | Nein / ja | ca. 6 (über dem Maximum) | 3 | Schwelle 4 |
| Sorel (Prediger) | wie Aldra | Nein / ja | ca. 6 | 3 | Schwelle 4 |
| Der Fremde | wie Aldra | Nein / ja | ca. 6 | 3 | Schwelle 4 |
| Orin (Konzernvertreter) | **heute kein Anlass**, Vorschläge in 1.4 | — | 0 heute | 2 | Schwelle 4 |

Hinweise zur Tabelle:

- Bei den drei Story-Figuren gibt es zwei Anlass-Wege (Tageswurf und Anliegen). Beide rufen dieselbe Funktion auf, ein Tag mit beiden Ereignissen öffnet trotzdem nur den **nächsten** Eintrag, soweit die Schwelle erfüllt ist. Wegen des Verfalls bremst die Schwelle hier stark; die hohe Rate spielt kaum eine Rolle, solange mindestens ein Anlass je Lauf stattfindet.
- Annahme (aus Code gelesen, nicht gemessen): Die Handelsfiguren zählen nur, wenn ein **benannter** Gast ein Angebot hat und der Spieler es annimmt. Der Anteil benannter Gäste am Gast-Strom ist aus der Config nicht ableitbar. Das ist die größte Unsicherheit der ganzen Liste.
- Bei Verfall der Anlässe entstehen für Spieler stille Verluste (Story-Tageswurf ohne Handlung). Dafür braucht es die In-Fiction-Zeile aus der Evaluation (z. B. Figur schweigt zu einer Sache); siehe Frage 8.

### 1.3 Tomas: Eintrag 5 über ein Gespräch

Ist-Stand: Jede fertige Cantina-Ausbaustufe öffnet den nächsten Eintrag (Stufe 5 öffnet Eintrag 5 nur, wenn die Zählung zufällig aufgeht). Das Gespräch „Mit Tomas reden“ (kostet keine Aktionspunkte, einmal je Sol) ruft `recordProgress` bisher nicht auf. Owner-Vorgabe: Eintrag 5 ohne Schwelle, aber erst nach einem Gespräch.

Vorschlag (Annahme): Die Einträge 1 bis 4 öffnen wie bisher über Ausbaustufen, aber mit den Schwellen der Tabelle. Eintrag 5 öffnet beim **ersten Gespräch mit Tomas, das nach Freischaltung von Eintrag 4 stattfindet**. Dafür ist die Gesprächs-Aktion neu an den Codex anzuschließen (eine Zeile im Controller, kein neuer Mechanismus). Folgen:

- Tomas kann damit schon in Lauf 4 vollständig sein (Schwelle 3 bei Eintrag 4, Eintrag 5 ohne Schwelle). Das unterschreitet die Untergrenze „frühestens 4 bis 5 Läufe“ für diese eine Figur leicht. Alternative: Eintrag 4 bei Tomas auf Schwelle 4 setzen; dann wäre er frühestens im fünften Lauf offen. Empfehlung: Schwelle 3 lassen, Tomas ist die einzige Figur ohne Zufall und ein Anker für den Spieler (siehe Frage 5).
- Eine Ausbaustufe, die zu einer Zeit fällt, in der die Schwelle für den nächsten Eintrag fehlt, verfällt. Da jeder Lauf mit neuer Kolonie beginnt, kommen die Stufen im nächsten Lauf erneut. Die Eintragsnummer ist also **nicht** an die Stufennummer gebunden, sondern an „die nächste fertige Stufe, bei der die Schwelle stimmt“ (Annahme, folgt aus dem Ist-Code).
- Offen: Muss für das Gespräch eine Mindeststufe der Cantina vorliegen (Frage 5)?

### 1.4 Orin: Auslöser (Vorschläge, Content-Frage)

Orin ist als „dedicated“ eingetragen, hat aber keine eigene Mechanik und keinen Aufruf. Drei Vorschläge, jeder nutzt nur vorhandene Bausteine:

1. **Orin bekommt den Kurzzeit-Kontrakt.** Der Kontrakt (Credits je Sol über ein festes Fenster) ist heute bewusst an keine Figur gebunden und teilt sich den Begegnungs-Slot mit Wette und Auktion. Orin wird sein Geber, Anlass ist die Annahme des Kontrakts. Rate etwa 3 je Lauf (Annahme (gerechnet): gleiche Rate wie Zara und Voss, ein Drittel des Begegnungs-Slots). Keine neue Mechanik, nur Zuordnung und Text. **Empfehlung**, weil Orins Rolle (Konzern, Vertrag) zum Kontrakt passt und die Cantina-Begegnungen sich dadurch nicht vermehren.
2. **Orin erscheint als zehnte Figur im Anliegen-Slot** (Gewicht 1, wie der Fremde und Vesper). Braucht eine neue Belohnung und neue Texte; Rate etwa 1 je Lauf. Gut für Variation, aber langsam und mit Inhaltsaufwand.
3. **Orin kommt zu Phasen-Meilensteinen** (eine Visite je erreichter neuer Phase). Rate 1 bis 4 je Lauf, abhängig vom Fortschritt, und damit eng an Erfolg im Lauf gekoppelt. Das widerspricht dem Grundsatz „kein Eintrag nur durch Erfolg“ nur schwach (Phase 2 erreicht fast jeder Lauf), ist aber eine neue Auslöse-Logik. Nicht empfohlen.

Die endgültige Wahl hängt am Inhalt (Frage 4).

## 2. Lore-Schicht der Regelartikel

Regelartikel bleiben immer offen. Nur der Lore-Teil je Artikel hat bis zu 3 Stufen. Ereignis-Muster und Schwellen (Annahme, grobes Muster; die Triggerpunkte in Gebäude-, Forschungs-, Berater- und Schiffscode habe ich **nicht** geprüft, nur das Muster festgelegt):

### 2.1 Schwellen-Muster für alle Lore-Stufen

| Lore-Stufe | Schwelle (Läufe gezählt) | Charakter |
|---|---|---|
| 1 | 0, bei Spät-Inhalten (Tempel, Denkmal, Korvette) 1 | Herkunft, kurzer Einstieg, erster Kontakt |
| 2 | 2 | Vertiefung nach echtem Umgang |
| 3 | 4 | Hintergrund, Geheimnis, Abschluss |

Wegen Verfall gilt auch hier: Der Anlass wird in einem späteren Lauf erneut erreicht. Stufe 3 hat Schwelle 4, damit die Lore wie die Figuren frühestens im fünften Lauf vollständig ist.

### 2.2 Anlass-Muster je Artikelart

| Artikelart | Stufe 1 | Stufe 2 | Stufe 3 |
|---|---|---|---|
| **Gebäude** (13 laut Entwurf) | erstmals gebaut | höhere Ausbaustufe fertig (Annahme: Stufe 2 oder 3, je Gebäude zu prüfen) | höchste im Normalfall erreichbare Ausbaustufe fertig (Annahme: nicht jedes Gebäude erreicht seine Maximalstufe im Lauf; zu messen) |
| **Kenntnisse** (7) | erstmals erforscht (Stufe 1 fertig) | Stufe 2 fertig | höchste Stufe fertig |
| **Berater** (4 sichtbar) | erstmals angestellt | Rang 2 erreicht | Rang 3 erreicht |
| **Schiffe** (3 aktiv: Drohne, Frachter, Korvette) | erstmals erhalten oder gekauft | erster Einsatz abgeschlossen (Annahme: Auftrag am Hangar) | mehrfacher Einsatz oder Reparatur nach Schaden (Annahme, Trigger noch zu klären) |

Rechenhinweise (**Annahme (gerechnet)**): 27 Artikel mal bis zu 3 Stufen sind bis zu 81 Lore-Stufen, davon ein Teil nur über den jeweils anderen Pfad (Labor, Hangar, Cantina) erreichbar. Das verteilt die Freischaltung natürlich auf verschiedene Läufe, macht „alles offen“ aber zugleich von der Pfadwahl abhängig (siehe Abschnitt 3, Cantina-lose Läufe sind das gleiche Problem für Gebäude des anderen Pfads). Tempel und Denkmal sind nur in erfolgreichen Läufen erreichbar; ihre Lore kommt daher faktisch nur in Siegen oder weit fortgeschrittenen Läufen, was bei der angestrebten hohen Siegquote unproblematisch ist, bei Niederlagen aber Lore dauerhaft verzögert.

## 3. Ab welchem Lauf ist „alles offen“ erreichbar?

Alle Zahlen in diesem Abschnitt sind **Annahme (gerechnet)**: ein einfaches Modell, keine Simulation, keine Messung.

**Modell.** Pro Lauf gibt es für jede Figur eine zufällige Zahl N von Anlässen (Poisson-Verteilung mit Mittel λ). Die Obergrenze des Laufs, `cap`, ist die höchste Eintragsnummer, deren Schwelle erfüllt ist. Ein Lauf bringt die Figur von k auf min(cap, k+N), weil überzählige Anlässe verfallen.

| Lauf | 1 | 2 | 3 | 4 | 5 |
|---|---|---|---|---|---|
| Obergrenze langsame Figur (Eintrag-4-Schwelle 2) | 2 | 3 | 4 | 4 | 5 |
| Obergrenze schnelle Figur (Eintrag-4-Schwelle 3) | 2 | 3 | 3 | 4 | 5 |

**Untergrenze.** Eintrag 5 ist frühestens im **fünften Lauf** möglich (vier gezählte Läufe davor, Schwelle 4). Das erfüllt die Vorgabe „frühestens nach 4 bis 5 Läufen“ bis auf Tomas (siehe 1.3), und nur dann, wenn jeder Lauf zählt.

**Rechenergebnis je Figurentyp** (Anteil der Figuren, die nach Lauf N vollständig sind):

| Figurentyp | λ | nach Lauf 5 | nach Lauf 6 | nach Lauf 7 |
|---|---|---|---|---|
| schnell (Story, Zara, Voss, Deva, Lenn) | 3 | ca. 0,88 | ca. 0,97 | ca. 0,99 |
| langsam | 1,5 | ca. 0,70 | ca. 0,88 | ca. 0,96 |
| sehr langsam | 1,0 | ca. 0,35 (grob geschätzt, nicht durchgerechnet) | ca. 0,6 | ca. 0,8 |

Aggregation (sehr grob, setzt unabhängige Figuren voraus): Nach Lauf 5 sind etwa 90 bis 95 Prozent aller 75 Einträge offen, nach Lauf 6 etwa 97 Prozent. **Alle 75 gleichzeitig** erreicht sehr wahrscheinlich erst Lauf 7 (Median, bei überwiegend langsamen Handelsfiguren mit λ 1,5) bis Lauf 9 (bei λ 1). Orin zählt hier schon mit Rate 3 (Vorschlag 1 in 1.4), ohne Auslöser wäre die Antwort „nie“.

**Wirkung von Läufen, die nicht zählen oder die Cantina nicht haben.** Jeder Lauf ohne Cantina erhöht den Zähler, liefert den 14 Cantina-Figuren aber keinen Anlass; er verschiebt „alles offen“ praktisch um einen ganzen Lauf. Bei einem von fünf Läufen ohne Cantina (**Annahme**, Anteil unbekannt) rückt der Median von Lauf 7 auf etwa Lauf 8 bis 9. Ein Lauf, der vor Sol 30 endet, zählt gar nicht.

**Lore der Regelartikel.** Wegen der festen Schwellen 0, 2 und 4 gilt dieselbe Untergrenze (fünfter Lauf). Die Verzögerung darüber hinaus hängt davon ab, wie oft ein Lauf bestimmte Ausbaustufen, Ränge und Pfadgebäude überhaupt erreicht; dazu gibt es keine Daten.

**Unsicherheiten, nach Gewicht:**

1. Rate der Handelsfiguren (Anteil benannter Gäste, Annahme der Evaluation 1 bis 2 je Lauf). Ändert den Median um bis zu 2 Läufe.
2. Anteil der Läufe ohne Cantina und ihr Gewicht in der Pfadwahl.
3. Erreichte Cantina-Ausbaustufe und Bauzeitpunkt (skaliert die Raten von Wette, Auktion, Anliegen um etwa den Faktor 2).
4. Anteil der Anlässe, die der Spieler tatsächlich auslöst (2 bis 4 Aktionspunkte je Annahme).
5. Lauflänge: Niederlagen enden früher und liefern weniger Anlässe, zählen aber ab Sol 30 für den Zähler.
6. Bot-Abhängigkeit: Bot-Läufe haben eine niedrigere Siegquote als Menschen (Owner: gewollt); eine Messung am Bot würde Cantina-Nutzung und Lauflänge nur näherungsweise abbilden.

## 4. Was noch fehlt

### Owner-Entscheidungen (mit Empfehlung)

1. **Wann steigt der Lauf-Zähler: am Lauf-Ende oder schon bei Sol 30?** Empfehlung: am Lauf-Ende (Schwellen ändern sich nie mitten im Lauf).
2. **Bestätigung: Schwellen global je Eintragsposition, Eintrag 4 gestreut (Schwelle 2 oder 3, Verteilung nach 1.1)?** Das war bisher nur eine Annahme.
3. **Verfall und Zählung der Eintragsnummern:** Bestätigung, dass ein Anlass immer den nächsten Eintrag öffnet und die Eintragsnummer nicht an einen bestimmten Anlass gebunden ist (Folge für Tomas, 1.3). Empfehlung: ja.
4. **Orin:** Welcher der drei Vorschläge? Empfehlung: Vorschlag 1 (Kontrakt), danach Inhaltsfrage an `content-writer`.
5. **Tomas:** Eintrag-4-Schwelle 3 (Tomas ggf. schon in Lauf 4 vollständig) oder 4? Dazu: Muss für das Gespräch, das Eintrag 5 öffnet, eine Mindestausbaustufe der Cantina vorliegen? Empfehlung: Schwelle 3, keine Mindeststufe.
6. **Lore-Schicht der Regelartikel:** Sind 3 Stufen mit Schwellen 0, 2, 4 in Ordnung, oder nur 2 Stufen für Kenntnisse und Schiffe? Empfehlung: 3 Stufen nur für Gebäude und Berater, 2 Stufen (0 und 3) für Kenntnisse und Schiffe, um den Textaufwand klein zu halten.
7. **Bestandsschutz:** Bereits geöffnete Einträge bleiben offen (Empfehlung: ja); die Schwellen gelten nur für neue Einträge, `runs_played` wird aus den beendeten Läufen des Spielers abgeleitet.
8. **Platzhalter bei Verfall:** Weil ein verfallener Anlass nicht gemerkt wird, kann „hält etwas zurück“ nicht am Anlass hängen. Empfehlung: Platzhalter nur nach dem Zustand des Eintrags (offen, gesperrt) und die In-Fiction-Zeile im Moment des verfallenden Anlasses (nur flüchtig, nicht gespeichert). Der Ergebnis-Hinweis ohne Zahl bleibt.

### Messungen (nur mit Freigabe des Owners für einen Bot-Batch und vorab abgestimmter Dauerschätzung, die Kosten bleiben bei dir)

1. Anlässe je Lauf und Figur, insbesondere Anteil benannter Handelsfiguren am Gast-Strom (größte Unsicherheit).
2. Zeitpunkt des Cantina-Baus, erreichte Ausbaustufe, Anteil der Läufe ohne Cantina.
3. Anteil der Läufe, die Sol 30 erreichen, und Lauflänge nach Ausgang.
4. Für die Regelartikel-Lore: Wie oft erreicht ein Lauf welche Ausbaustufe, welchen Rang und welche Kenntnis-Stufe; Einsatzhäufigkeit der Schiffe.
5. Anteil der Anlässe, die ein Mensch tatsächlich auslöst (nicht aus dem Bot ableitbar; Bot-Verhalten ist hier eine offene Variable).

### Offen für `content-writer` und Entwicklung (nicht Teil dieser Liste)

Eintragstexte, Orin-Inhalt, In-Fiction-Zeilen für verfallene Anlässe, Prüfung der Trigger-Stellen für die Regelartikel-Lore, technische Umsetzung des Lauf-Zählers (TDD).
