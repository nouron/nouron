# Datenarchiv: Redaktionsideen und Platzhaltertexte (Stand 2026-11-09)

Status: **grobe Ideen und Platzhalter, kein Feinschliff, nichts umgesetzt.** `lang/*` ist unverändert. Eigene Deutungen sind mit **(Annahme)** gekennzeichnet. Zahlen, Schwellen und Anlass-Mengen legt `game-designer` fest und stehen hier bewusst nicht. Grundlage: Entwurf `2026-11-08-nexusdb-codex-zusammenfuehren-entwurf.md` (Owner-Kasten, Redaktionskonzept) und Evaluation `2026-11-09-datenarchiv-freischalt-evaluation.md`.

Hinweis zu den Namen: Im Auftrag stehen „Wissenschaftler“ und „Händler“. In `lang/de/advisors.php` heißen die sichtbaren Berater **Baumeister, Analytiker, Raumfahrer, Konsul** (der Stratege ist der fünfte, hier nicht sichtbar). Dieses Dokument nutzt die Spielnamen.

## 1. Ton- und Stilrichtlinie der Lore-Schicht

**Zwei Schichten, zwei Stimmen.**

| | Regelschicht | Lore-Schicht |
|---|---|---|
| Stimme | Nexus-Almanach (Ausnahme B): akademisch-präzise, passiv, ohne Wärme | Kolonistenblick: warm, atmosphärisch, aus dem Alltag (Grundton des Spiels) |
| Status | immer offen | gesperrt, in Stufen freischaltbar |
| Inhalt | Wirkung, Kosten, Stufen | Herkunft, Gewohnheiten, Gerüchte, kleine Szenen |

**Regeln für Lore-Einträge.**

1. **Länge:** 1 bis 3 Sätze je Eintrag, nie mehr. Ein Eintrag = ein Gedanke, ein Bild.
2. **Zeigen, nicht erklären:** ein konkretes Detail (Geruch, Geräusch, Gewohnheit) statt einer Zusammenfassung. Muster der bestehenden Figuren-Einträge: Beobachtung, Gedankenstrich, offene Pointe oder Gerücht.
3. **Offene Enden:** Lore beantwortet nicht alles. „Niemand fragt mehr nach“ ist besser als eine Auflösung. Spätere Stufen dürfen vertiefen, nicht widerlegen.
4. **Spoilerregel:** Lore verrät keine Mechanik, die der Spieler noch nicht gesehen hat. Konkret: keine Zahlen, keine Namen noch nicht freigeschalteter Gebäude, Kenntnisse oder Schiffe, keine Hinweise auf Begegnungsausgänge, Siegziele oder spätere Phasen. Eine Stufe darf nur Dinge nennen, die der Spieler mit dem jeweiligen Anlass bereits erlebt hat. Sensible Lore gehört in die späten Stufen (siehe Evaluation, Spoiler-Risiko).
5. **Keine versteckte Regelhilfe:** Lore ist kein Tipp-Kanal. Wirkung nie als Fakt, höchstens als Stimmung („seit er da ist, wird es abends ruhiger“).
6. **Vokabular:** „Zwischenfall“, „Begegnung“, „Event“; nie Kampf, Krieg, Angriff. „Kolonisten“ oder „Siedler“, wenige hundert. Keine Fraktionen, keine Diplomatie. Nexus kommt nur als ferne Behörde vor, nie als Gesprächspartner.
7. **Perspektive:** Menschen sprechen oder werden gesehen, Nexus-Sprache bleibt der Regelschicht vorbehalten. Ausnahme: Einträge, in denen die Kolonisten über Nexus reden, dürfen respektlos-trocken sein (Alltag, kein Grimdark).
8. **Regelartikel bleiben nüchtern:** Die Lore-Schicht ändert keinen Wortlaut der Regelschicht. Querverweise zu Figuren stehen in einem eigenen, abgesetzten Block (siehe Abschnitt 5), nicht im Regeltext.
9. **Namensfreie Figuren:** Wo eine Figur noch nicht begegnet ist, wird sie nirgends genannt (auch nicht in Lore anderer Artikel). **(Annahme)**
10. **Wiederverwendung:** Kein Satz doppelt zu den bestehenden `codex_entry_*`; Lore-Stufen bei Artikeln dürfen Figuren nur dann namentlich erwähnen, wenn der Eintrag der Figur dieselbe Stufe schon trägt (sonst Spoiler gegen die Figur).

## 2. Lore-Stufen je Artikel (Platzhalter)

Konvention: **Stufe 1** = leicht erreichbar, Stimmung/Alltag. **Stufe 2** = Vertiefung, ein Geheimnis oder eine Person. **Stufe 3** = Wendung oder offene Frage, nur für Spät-Anlässe. Bis zu 3 Stufen pro Artikel (Owner-Vorgabe), manche Artikel bekommen bewusst nur 1 bis 2. Die Spalte „Anlass“ ist ein Vorschlag des Anlass-**Typs**; Schwellen und Anzahlen kommen von `game-designer`. Stichworte statt fertiger Sätze; „Rohtext“ markiert einen ersten Satzentwurf.

Anlass-Typen: **Bau** (erste Platzierung), **Ausbaustufe** (Stufe n erreicht), **Erforschung** (Kenntnis fertig), **Einstellung** (Berater angestellt), **Rang** (Berater befördert), **Kauf** (Schiff gekauft). Zusätzlich möglich: **Ereignis** (z. B. Verfall, Zwischenfall); als Typ noch nicht in der Evaluation, deshalb nur als Anregung. **(Annahme)**

### 2.1 Gebäude (13)

| Gebäude | Stufe 1 | Stufe 2 | Stufe 3 | Anlass |
|---|---|---|---|---|
| Kommandozentrale | Rohtext: „Das erste Modul, das gesetzt wurde. Wer genau hinsieht, findet Kratzer von der Landung.“ | Namen, die jemand mit Marker an die Innenwand schrieb | Ein Raum, den niemand betritt (offen lassen) | Bau (immer da, daher Start) -> eher **Ausbaustufe** |
| Wohnhabitat | Enge, Gerüche, Wände dünn wie Papier | Kinderzeichnung an der Schleuse; wer wen wegen Schnarchen verfluchte | Warteliste für die „guten“ Betten | Bau, dann Ausbaustufe |
| Harvester | Das Surren, das man nach einer Woche nicht mehr hört | Der Spitzname, den die Schichtarbeiter dem Gerät gaben | Eine Bohrprobe, die niemand analysieren ließ | Bau; Stufe 3 über **Erforschung** (Geologie) |
| Agrardom | Duft von feuchter Erde, den es sonst nirgends gibt | Pilzkultur, die als Haustier gilt | Ein Beet, das nur eine Person gießen darf | Bau; Stufe 2 Erforschung (Agronomie) |
| Analytik-Labor | Enge, Kaffee, Messwerte an den Wänden | Streit um die Rechenzeit | Eine Messreihe, die niemand erklären kann | Bau; Stufe 2 Erforschung; Stufe 3 Einstellung (Analytiker) |
| Hangar | Öl, Kälte, Warten auf Rückkehrer | Die Geschenk-Drohne hat einen Namen | Eine Bucht, die immer frei gehalten wird | Bau, dann Ausbaustufe (Buchten) |
| Krankenstation | Das Bett, das nie kalt wird | Wer zuerst Patient war | Schweigen über Verluste | Bau; Stufe 2 Erforschung (Gesundheit) |
| Cantina | Lärm, Gläser, Gerüchte | Wer den Stammplatz hat; Regeln, die keiner aufschrieb | Warum es hier keine Streits gibt (Tomas deutet an) | Bau; Stufen mit Ausbaustufen (passt zu Tomas, siehe Figurenbereich) |
| Kolonialdenkmal | Eine Namenswand, noch fast leer | Wer die ersten Namen eingravierte | Ein Name, der nicht dort stehen sollte | Bau (Owner: Lore erst nach dem ersten Bau); Stufe 3 Ausbaustufe |
| Religiöse Stätte | Ein Raum, den jeder meidet und trotzdem betritt | Gegenstände von zu Hause, die dort liegen | Wer nachts dort sitzt | Bau; passt zu Sorel (Querverweis) |
| Leitstelle | Das Funkrauschen, das man hören lernt | Wer zuerst eine Meldung zu spät sah | Ein Alarm, der nie ausgelöst wurde | Bau; Stufe 2/3 **Ereignis** (Zwischenfall überstanden) |
| Uplink-Station | Der Himmel über der Antenne ist die einzige Verbindung | Nachrichten, die Wochen alt ankommen | Ein Signal, das nach Nexus klang, aber nicht war (offen) | Bau; Stufe 2 Ausbaustufe |
| Handelsposten | Provisorischer Tresen, handgeschriebene Preise | Stammhändler mit Spitznamen | Ein Händler, der nie wieder kam | Bau; passt zu Dax/Fen (Querverweis) |

### 2.2 Kenntnisse (7)

Lore wird erst **nach Erforschung** geöffnet (Entwurf, Tabelle „immer verfügbar“). Anlass-Typ daher überall **Erforschung**; Stufe 2 und 3 können an eine zweite/dritte Stufe der Kenntnis oder an einen Folge-Anlass hängen. **(Annahme)**

| Kenntnis | Stufe 1 | Stufe 2 | Stufe 3 |
|---|---|---|---|
| Bautechnik | Wie man mit Werkzeug umgeht, das nie für diese Schwerkraft gedacht war | Eine Regel der ersten Bauleute, die niemand mehr begründen kann | Ein Fundament, das nie angetastet wurde |
| Kartografie | Karten, die eine Person von Hand korrigiert | Welche Gegenden die Alten meiden | Ein weißer Fleck, den man freiwillig lässt |
| Geologie | Gesteinsproben mit Spitznamen | Was der Untergrund „hören lässt“ | Eine Ader, die sich nicht abbauen lässt |
| Agronomie | Erste Ernte, die komisch schmeckte | Rezepte, die aus Not erfunden wurden | Eine Pflanze, die nicht gezüchtet wurde, sondern einfach da war |
| Gesundheit | Hausmittel aus der alten Heimat | Wer wem welche Medizin verdankt | Krankheit, die nie einen Namen bekam |
| Handel | Preise, die sich herumsprechen | Die ungeschriebenen Regeln des Tauschens | Ein Handel, der alle überraschte |
| Verteidigung | Routinen, die man irgendwann ohne Nachdenken macht | Wie Zwischenfälle in Erzählungen zu Legenden werden | Ein Zwischenfall, über den man nicht redet |

### 2.3 Berater (4 sichtbar)

Anlass-Typen für Berater: **Einstellung** (Stufe 1), **Rang** (Stufe 2 und 3). Namenlose Rollen, keine Einzelfiguren; die Lore beschreibt die Rolle in der Kolonie, nicht eine Person. **(Annahme)** Kein Hinweis auf Boni oder Rangwerte.

| Berater | Stufe 1 (Einstellung) | Stufe 2 (Rang) | Stufe 3 (höherer Rang) |
|---|---|---|---|
| Baumeister | Die Werkzeugtasche, die älter ist als der Träger | Kennt die Macken jedes Moduls | Wird gerufen, wenn „nur noch er“ hilft |
| Analytiker | Schreibt abends Notizen auf Papier, weil es sich sicherer anfühlt | Fragen, die sie nie laut stellt | Hat etwas gefunden und sagt es erst, wenn sie sicher ist |
| Raumfahrer | Spricht lieber mit dem Himmel als mit Menschen | Karten im Kopf, nicht im Gerät | Kam nicht zufällig in diese Kolonie |
| Konsul | Verhandelt auch beim Frühstück | Weiß, wer Schulden hat, und schweigt | Verdankt der Kolonie mehr, als er zugibt |

### 2.4 Schiffe (aktive: Drohne, Korvette, Frachter)

Anlass-Typ **Kauf**. Stufe 2 und 3 optional (**Ereignis**, z. B. erste Mission abgeschlossen), sonst nur 1 Stufe. **(Annahme)**

| Schiff | Stufe 1 (Kauf) | Stufe 2 | Stufe 3 |
|---|---|---|---|
| Drohne | Leises Summen beim Start; Kolonisten geben ihr Spitznamen | Einmal ist sie von selbst zurückgekehrt | Aufnahmen, die nie jemand angesehen hat |
| Korvette | Stolz der Hangarcrew, lackiert statt grau | Patrouillenrouten, die zur Gewohnheit wurden | Besatzung, die nie nach Hause schreibt (offen) |
| Frachter | Wirkt auf dem Vorfeld größer als jedes Gebäude | Stammstrecken mit Kosenamen | Eine Fracht, die verschwand |

## 3. Orin: Auslöser und Figurenidee

**Befund:** Orin hat im Code keinen Auslöser (Evaluation 1.2). Dazu: `config/characters.php` führt ihn als `dedicated` („mechanic open“), sein Charakterbogen als `Game Role: information`. Das ist ein Widerspruch, der vor der Umsetzung aufzulösen ist (an `game-designer` / `backend-coder`).

Orin soll nach Bogen und Codex-Einträgen ein Mann bleiben, über den niemand sicher ist: tritt auf, wenn Lieferverträge, Abbaurechte oder Regelfragen anstehen, nie ohne Grund.

**Vorschlag A: Orin erscheint bei Nexus-Schulden/Subvention.** Er taucht auf, wenn der Direktor Kredit aufnimmt oder eine Subvention bekommt. Idee: Er „hört“ vom Ereignis und sitzt kurz danach in der Cantina, höflich, ohne etwas zu verlangen. Anlass = Ereignis „Kredit/Subvention“. Vorteil: passt exakt zu seiner Rolle (Nexus-nahe Interessen) und zum Charakterbogen. Nachteil: Kopplung an Nexus-Mechanik; Spieler ohne Kredit bekommen ihn selten. **(Annahme: Kreditaufnahme ist ein klar auslösbares Event.)**

**Vorschlag B: Orin erscheint beim Bau eines Handelsposten/Uplink (Infrastruktur).** Er interessiert sich für Anlagen, die „Konzessionswert“ haben. Anlass = Bau Handelsposten oder Uplink-Station, einmal je Gebäude. Vorteil: sicher ausgelöst, verteilt sich über den Lauf, gut testbar, kaum Kopplung an Zufall. Nachteil: Spieler mit anderem Pfad bekommt ihn evtl. später.

**Vorschlag C: Orin erscheint bei einem Zwischenfall (Zufallsbegegnung in der Cantina).** Wie die Story-Figuren: ein seltener Gast, der ohne Spielerhandlung auftaucht. Vorteil: wirkt wie „Zufall, der keiner ist“, passt zum Mysterium. Nachteil: viel Streuung, gegen die Planbarkeit des Freischalt-Budgets (Evaluation 1.3).

**Empfehlung: B, ergänzt durch A als zweiten Anlass.** B liefert verlässliche, planbare Anlässe über den Lauf; A gibt der Figur Bedeutung und ist ein Kandidat für die späten Einträge (Eintrag 4 und 5, wenn ohnehin eine Schwelle greift). C verwerfen, weil das Budget nicht planbar wird. Figurenidee dazu (**Annahme**, Charakterbogen unverändert): Orin tritt nie mit Forderung auf, sondern „stellt Fragen zur Lage“; der Spieler merkt erst später, dass jede Frage ein Datenpunkt war. Das passt zu Eintrag 5 („was er will, deckt sich selten mit dem, was er sagt“). Ob Orin eine eigene Mechanik bekommt, entscheidet `game-designer`, nicht dieses Dokument.

## 4. Platzhaltertexte (je 2 Varianten, zunächst nur de)

Vom Owner freigegeben ohne Zahl. Englische Fassung später (`lang/en`).

**4.1 Platzhalter „noch nicht begegnet“** (kein Anlass; kein Name, kein Titel)

- A: „Hier gibt es noch nichts zu lesen. Wer dem Namen begegnet, findet ihn hier wieder.“
- B: „Ein leerer Eintrag. Sobald sich etwas ergibt, steht es hier.“

**4.2 Platzhalter „hält etwas zurück, später mehr“** (Anlass angespart, Schwelle fehlt; kein Spoiler, keine Schwelle)

- A: „Es gibt mehr zu erzählen, aber nicht jetzt. Beim nächsten Mal vielleicht.“
- B: „Da ist noch etwas, das nicht gesagt wird. Es braucht Zeit.“

**4.3 Hinweis im Cantina-Dialog bei Anlass ohne Freischaltung** (In-Fiction-Zeile, keine Systemsprache, keine Zahl)

- A: „Sie sieht dich einen Moment lang an und schüttelt dann den Kopf. ‚Ein andermal.‘“
- B: „Er will noch etwas sagen, überlegt es sich dann anders und bestellt noch eine Runde.“

**Hinweis (Annahme):** Zeilen müssen figurenneutral formulierbar sein; die Beispiele nutzen deshalb nur Pronomen. Pro Figur individuelle Zeilen wären später möglich (z. B. Tomas: „Frag mich nach der nächsten Runde.“), bräuchten aber 15 Texte statt 2. Empfehlung: erst neutral.

**4.4 Hinweis auf der Ergebnisseite „Neue Einträge im Datenarchiv“** (ohne Zahl)

- A: „Das Datenarchiv hat sich erweitert. Neue Einträge warten auf dich.“
- B: „Aus diesem Lauf ist etwas im Datenarchiv hängengeblieben. Sieh nach.“

Hinweis (**Annahme**): Die Ergebnisseite zeigt den Hinweis nur, wenn tatsächlich etwas freigeschaltet wurde. Die Meldung ist Nexus-Stil (Ausnahme A) oder Kolonie-Ton? Empfehlung: Kolonie-Ton, weil das Datenarchiv als Spielort-Erlebnis gedacht ist; Nexus-Ton (kurz, passiv) wäre Variante C: „Datenarchiv: Einträge ergänzt.“

## 5. Figurenbereich: Querverweis-Formulierungen

Der Bereich „Figuren“ bleibt ein eigener Abschnitt; bei Regelartikeln erscheint ein abgesetzter Querverweis-Block („Aus der Kolonie“) mit einem Satz. Er verlinkt auf die Figur, öffnet nur Einträge, die der Spieler schon hat, und zeigt sonst nichts. **(Annahme: der Block erscheint erst, wenn der verknüpfte Figuren-Eintrag freigeschaltet ist, sonst verrät der Verweis die Figur.)**

Fünf Beispiele:

1. **Cantina** -> Tomas: „Tomas erwähnt, dass er jedes Gesicht kennt, das hier zweimal saß.“
2. **Religiöse Stätte** -> Sorel: „Sorel spricht von einem Ort, an dem man nicht glauben muss, um zu schweigen.“
3. **Handelsposten** -> Dax: „Dax kennt Depots, die in keinem Register stehen — ein Posten wie dieser lässt ihn vorsichtiger werden.“
4. **Krankenstation** -> Maret: „Maret sagt, ihr Dienst ende offiziell um 22 Uhr. Die Station sieht das anders.“
5. **Agrardom** -> Fen: „Fen erzählt, sie habe Erz gerochen, bevor sie wusste, was Erde sein kann.“ (**Annahme:** Fen, Fund-Lore, kein Pflichtbezug)

Weitere Muster: „X erinnert sich an …“, „Laut Y …“, „Y will wissen, wie …“. Verboten: Querverweise, die Regelwirkung verraten („Maret sagt, die Station erhöht das Vertrauen“).

## 6. GLOSSAR-Eintragsvorschlag

Für die Tabelle „Spielbegriff ↔ Code-Schlüssel ↔ Bedeutung“:

| Datenarchiv | – (Route `/nexus-db`, API `/codex`) | Das Nachschlagewerk des Spiels: Regelartikel (immer offen) und Lore-Einträge zu Gebäuden, Kenntnissen, Beratern, Schiffen und Figuren (stufenweise freigeschaltet, spielerweit). Alias „Nexus-DB“ (Lore-Name); frühere Bezeichnung „Codex“ nur noch intern. |

Ergänzung zu „Namens-Fallen“ (ersetzt die jetzige Zeile „Nexus-DB (Enzyklopädie) ↔ Nexus (Schulden/Subvention)“):

- **Datenarchiv** (alias Nexus-DB, Nachschlagewerk) ↔ **Nexus** (Behörde hinter Subvention, Kredit und Schulden, `nexus_debt`). „Nexus-DB“ meint nie die Schulden.

Und die Zeile der Tabelle „Nexus“ kürzen auf: „Die Behörde hinter Subvention, Kredit und Schulden. Das Datenarchiv (Alias Nexus-DB) ist dagegen das Nachschlagewerk.“

## Offene Fragen

1. **Orin:** `dedicated` (Config) gegen `information` (Bogen): was gilt? Erst klären, dann Auslöser wählen.
2. **Ereignis-Anlässe** (Zwischenfall überstanden, Kredit aufgenommen) sind als Anlass-Typen für Lore in der Evaluation nicht vorgesehen. Gewollt oder nur Bau/Ausbaustufe/Erforschung/Einstellung/Rang/Kauf?
3. **Berater-Namen:** Bleiben Berater namenlose Rollen oder soll es Lore zu Einzelpersonen geben? (Annahme: namenlose Rollen.)
4. **Dopplung Tomas/Cantina:** Dürfen Cantina-Stufen und Tomas’ Einträge 1 bis 5 dieselben Ausbaustufen als Anlass nutzen, oder soll Tomas’ Fortschritt eigene Anlässe haben?
5. **Ergebnisseite:** Nexus-Ton oder Kolonie-Ton (Abschnitt 4.4)?
6. **Querverweis-Block:** Erscheint er erst nach Freischaltung des Figuren-Eintrags (Empfehlung) oder immer als Hinweis „Hier gibt es mehr“?
7. **Fen-Beispiel (Abschnitt 5):** Hat Fen eine Verbindung zum Agrardom oder ist das zu frei erfunden? Alternativ ein Beispiel mit Sarka (Wohnhabitat/Verteilersysteme).
