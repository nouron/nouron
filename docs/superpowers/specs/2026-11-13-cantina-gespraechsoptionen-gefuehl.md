# Cantina-Gespräche — ein Gespräch je Figur und Sol

Stand: 2026-11-13 (zweite Fassung) · Autor: game-designer · Status: **entschieden** (Owner 2026-11-13): Variante C, Warnung als Chip plus Hinweiszeile, Tempo in Sol festhalten. **AP-Zahlen dieser Spec sind überholt** — es gelten die Werte aus `2026-11-13-ap-skala.md` §8 und §11.4 (Figur höchstens 2 AP je Sol, Beziehungsstufen 1/3/3/3/3, langes Gespräch ab Cantina 3).

Anlass (Owner, Playtest 2026-11-13): „Technisch funktioniert es wie gewollt. Ich habe nur ein ‚Gefühlsproblem‘: Ich konnte jetzt 6x die Option ‚Am Tisch vorbeischauen (1AP)‘ anklicken … das wirkt für mich unrund.“

Verwandt: GDD §4b „Vertiefung“ (Absatz „Cantina-Stufe staffelt die Gesprächsoptionen“), `2026-11-11-tomas-rat-rueckmeldung.md`, GLOSSAR „Gespräch“, „Beziehungsstufe“.

> **Vorbehalt:** Alle Zahlen in dieser Spec stehen unter dem Vorbehalt einer möglichen globalen Neukonzipierung der AP (Owner-Idee 2026-11-13: verfügbare *und* benötigte AP bei Bau und Forschung insgesamt verringern; **nicht entschieden**). Was bei einer solchen Umstellung erhalten bleiben soll, ist das **Tempo in Sol** (§5), nicht die AP-Zahlen. Die AP-Werte werden dann im selben Verhältnis mitskaliert.

---

## 0. Owner-Entscheidungen (2026-11-13, verbindlich)

| # | Entscheidung |
|---|---|
| E1 | Optionen, die das Sol-Limit übersteigen, sind nicht auswählbar. |
| E2 | Kein Übertrag von Überschuss-AP in die nächste Beziehungsstufe (kein Prinzip wie bei der stillen Forschung). |
| E3 | Kein Kürzen der AP: Eine Option kostet immer genau ihren angezeigten Preis. |
| E4 | **Stufengrenze: voller Preis, der Rest verfällt, mit transparenter Warnung an den Spieler** (nicht sperren). |
| E5 | Eine Figur nimmt höchstens so viele AP je Sol auf wie Bau, Labor oder Training, also 4. |
| E6 | Variante B (erste Fassung: drei Basisoptionen, jede einmal je Sol) ist dem Owner zu langsam. Variante C soll ausgearbeitet werden. |

---

## 1. Befund (gelesen aus Config und Code, nicht gemessen)

| # | Befund | Quelle |
|---|---|---|
| B1 | Drei Gesprächsoptionen je Figur: kurz 1 AP, normal 2 AP, ausführlich 4 AP. Offen ab Cantina-Stufe 1 / 2 / 3. | `config/path_depth.php` `conversation_options`, `conversation_option_cantina_level` |
| B2 | Jede Option ist beliebig oft je Sol wählbar, bis der Tagesdeckel je Figur (6 AP) voll ist. | `conversation_ap_cap_per_sol.base` |
| B3 | **Die Cantina-Stufe ändert das Tempo heute gar nicht.** Mit Cantina 1 füllt man den Deckel mit sechs 1-AP-Gesprächen, mit Cantina 3 mit 4 + 2. Die Sperre kostet nur Klicks und bremst nicht. Das ist der Kern des „unrunden“ Gefühls. | gerechnet aus B1 + B2 |
| B4 | Optionen werden heute auf den Rest bis zur nächsten Stufe gekürzt. Nach E3 entfällt das. Beim Aufstieg wird der Fortschritt auf 0 gesetzt; das bleibt (E2). | `PathDepthService.php:137-141`, `:191` |
| B5 | Beziehungsstufen kosten 4 / 6 / 8 / 10 / 12 AP, zusammen 40 AP bis „brüderlich“. | `levelup_ap.character` |
| B6 | Die Cantina-Stufe bringt schon heute anderes: Vertrauen je Stufe und mehr bzw. längere Angebote. | `config/buildings.php` `bar` |

---

## 2. Variante C — die Regel

**Ein Gespräch je Figur und Sol. Der Spieler wählt, wie lange es dauert.** Die Länge ist der AP-Einsatz.

- Je Figur gibt es Gesprächslängen von 1 AP bis zur Höchstlänge. Welche Längen offen sind, entscheidet die Cantina-Stufe (§3).
- Nach dem Gespräch ist die Figur für heute fertig. Am nächsten Sol ist sie wieder ansprechbar.
- **Ein eigener Tagesdeckel je Figur ist überflüssig.** Bei einem Gespräch je Sol ist die längste offene Länge zugleich das Tagesmaximum. Mit einer Höchstlänge von 4 AP ist E5 schon durch die Optionen erfüllt. `conversation_ap_cap_per_sol` kann auf `null`, und E1 („über dem Sol-Limit nicht wählbar“) kann gar nicht eintreten, weil keine offene Länge über dem Limit liegt.
- Eine Gesprächslänge über dem Rest bis zur nächsten Stufe ist wählbar. Sie kostet den vollen Preis, und der Überschuss verfällt mit Warnung (E4, §6).
- Ist die Figur „brüderlich“, gibt es keine Gesprächsoptionen mehr (wie heute).

**Was die Entscheidung ist:** „Wie viel von meinem gemeinsamen Pool gebe ich heute dieser Figur?“ Das ist eine Frage je Figur und Sol. Kürzer zu reden hat zwei gute Gründe: AP für anderes übrig behalten, oder genau den Rest bis zur nächsten Stufe treffen, statt etwas verfallen zu lassen.

---

## 3. Cantina-Stufe in Variante C

**Vorschlag: Die Cantina-Stufe verlängert das mögliche Gespräch um eine Länge.**

| Cantina | offene Längen | Tagesmaximum je Figur |
|---|---|---|
| 1 | 1, 2 | 2 AP |
| 2 | 1, 2, 3 | 3 AP |
| 3 | 1, 2, 3, 4 | 4 AP (= Deckel E5) |

- Das passt genau zu den drei Ausbaustufen und endet beim Deckel. Die Regel ist in einem Satz erklärbar („mit jeder Ausbaustufe kannst du ein Stück länger bleiben“).
- Weil alle Längen von 1 bis zum Maximum offen sind, lässt sich jeder Rest bis zur nächsten Stufe genau treffen. **Verfall entsteht also nie zwangsläufig**, sondern nur, wenn der Spieler bewusst länger redet.
- Gesperrte Längen bleiben sichtbar, grau, mit Schloss und Figurenzeile (Content gibt es schon). Sie sind der Hinweis auf den Ausbau.
- Der Ausbau-Anreiz kommt zusätzlich zu B6 (Vertrauen, Angebote).

**Alternative C′ (nicht empfohlen):** alle vier Längen ab Cantina 1, der Ausbau wirkt nur über B6 oder spätere Figurenwirkungen (P7). Das ist einfacher, nimmt aber dem Ausbau die direkte Wirkung auf die Beziehungen, und B3 käme in neuer Form zurück.

---

## 4. Erzählung und UI

- **Kopfzeile im Figuren-Popup:** „Wie viel Zeit nimmst du dir heute für <Name>?“
- **Knöpfe nach Länge, je mit AP-Chip** (Konvention AP-Kosten-Chip): z. B. „Am Tisch vorbeischauen · 1 AP“, „Zuhören · 2 AP“, „Ein Glas zusammen · 3 AP“, „Die halbe Nacht reden · 4 AP“. Die Namen wechseln je Figur und Sol wie heute.
- **Nach dem Klick sind alle Knöpfe für heute aus.** An ihrer Stelle steht eine Figurenzeile passend zur gewählten Länge (kurz: knapp und freundlich, lang: persönlich), dann das Ergebnis („Vertrautheit +3 · noch 2 bis ‚vertraut‘“) und der Chip „wieder nächstes Sol“. Das ist dasselbe Muster wie bei Tomas' Rat („Für heute ist alles gesagt“), der Spieler kennt es also schon.
- **Am Tresen:** ein kleines Zeichen an Figuren, mit denen heute noch nicht gesprochen wurde (*Annahme*; nach Tomas' Zeichen für den offenen Rat, A11 der Tomas-Spec). Bei vielen Figuren eventuell zu viel Unruhe, nach Playtest prüfen.
- Tomas' Rat bleibt eine eigene Handlung (einmal je Sol) neben dem Gespräch.

---

## 5. Tempo (Annahme, gerechnet, nicht gemessen)

**Rechenregel:** Ohne Übertrag (E2) endet jeder Sol spätestens an der nächsten Stufengrenze. Sol je Stufe = aufgerundet (Stufenkosten ÷ Tagesmaximum). Spieler oder Bot redet jeden Sol mit dieser Figur, und der Pool reicht dafür.

Angegeben: Sol bis **bekannt** (Stufe 2) / **vertraut** (Stufe 3) / **brüderlich** (Stufe 5) für eine Figur.

| Stufenkosten (`levelup_ap.character`) | Summe | Cantina 1 (2 AP/Sol) | Cantina 2 (3 AP/Sol) | Cantina 3 (4 AP/Sol) |
|---|---|---|---|---|
| **Heute 4 / 6 / 8 / 10 / 12** | 40 | 5 / 9 / 20 | 4 / 7 / 15 | 3 / 5 / 11 |
| 2 / 4 / 6 / 8 / 10 | 30 | 3 / 6 / 15 | 3 / 5 / 12 | 2 / 4 / 9 |
| 3 / 4 / 5 / 6 / 7 | 25 | 4 / 7 / 14 | 3 / 5 / 10 | 2 / 4 / 8 |
| **2 / 3 / 4 / 5 / 6 (Vorschlag)** | 20 | 3 / 5 / 11 | 2 / 4 / 8 | 2 / 3 / 7 |

Vergleich heute (6 AP/Sol, Kürzung, beliebig oft klicken): 2 / 3 / 7 Sol, unabhängig von der Cantina-Stufe.

**Ziel-Tempo (Annahme, nicht Owner-entschieden):**
- **bekannt** in 2–3 Sol: früh spürbar, Tomas' Rat lohnt sich schnell.
- **vertraut** in 3–5 Sol.
- **brüderlich** mit voll ausgebauter Cantina in etwa einer Woche (≈ 7 Sol, so schnell wie heute), mit Cantina 1 rund anderthalbmal so lange (≈ 11 Sol).
- Begründung: Ein Lauf dauert realistisch 85–95 Sol, die Cantina steht frühestens ab dem Ende von Phase 1. Es bleiben grob 60 Sol Cantina-Zeit, und der Pool verteilt sich auf mehrere Figuren und andere Aufgaben. „Brüderlich“ mit einer Figur soll ein spürbarer, aber erreichbarer Einsatz sein, und zwei bis drei tiefe Beziehungen je Lauf sollen möglich sein.

**Empfehlung: Stufenkosten 2 / 3 / 4 / 5 / 6.** Das ist die einzige Zeile, die das Ziel trifft. Mit Cantina 3 entspricht sie dem heutigen Tempo, darunter ist sie langsamer, und die Cantina-Stufe wirkt dadurch wirklich (B3 behoben). Die Zahlen sind außerdem klein (Owner-Leitlinie) und passen zur möglichen AP-Verringerung. Die heutigen Kosten wären unter dem 4-AP-Deckel klar zu langsam (brüderlich frühestens nach 11 bzw. 20 Sol).

**Offene Variablen:** Der gemeinsame AP-Pool bremst vermutlich stärker als der Deckel je Figur. Wie viele Figuren ein Spieler parallel pflegt und ob der Bot Beziehungen überhaupt pflegt, ist unbekannt. Ohne eine Bot-Regel misst ein Batch hier nichts.

> ⚠️ BALANCE CONCERN: Tomas' Rat (deckelfreie Kenntnis-AP, stille Forschung) steigt mit seiner Beziehungsstufe. Mit 2 / 3 / 4 / 5 / 6 und Cantina 3 ist Tomas nach gerechnet 3 Sol „vertraut“, heute ist das ebenfalls nach 3 Sol der Fall. Für Tomas ändert sich mit Cantina 3 also nichts, mit Cantina 1 und 2 wird er langsamer. Das Risiko liegt nicht höher als heute (Annahme).
>
> Hypothese für die Messung (ein Hebel: Variante C mit 2 / 3 / 4 / 5 / 6 gegen heute): Cantina-First erreicht bis Sol 40 bei Tomas im Mittel dieselbe Beziehungsstufe wie heute, ±1. Widerlegt, wenn die Abweichung zwei Stufen oder mehr beträgt.

---

## 6. Warnung an der Stufengrenze (E4)

Vor dem Klick, nicht danach. Ohne Bestätigungsdialog, denn der wäre bei einem Gespräch je Sol zu viel Reibung (*Annahme*).

- **Im Knopf:** Der AP-Chip zeigt immer den vollen Preis (E3). Längen, bei denen etwas verfallen würde, bekommen zusätzlich einen Warnchip in der Warnfarbe: „3 AP · 2 verfallen“.
- **Hinweiszeile über den Knöpfen**, nur wenn mindestens eine Länge betroffen ist: „Bis ‚vertraut‘ fehlt nur noch 1 AP. Längere Gespräche bringen nicht mehr Fortschritt, der Rest verfällt.“
- **Ergebniszeile nach dem Klick**, falls etwas verfallen ist: „<Name> ist jetzt vertraut. 2 AP verfallen.“ Das gehört auch ins Protokoll.
- Die passende Länge (genau der Rest) wird nicht hervorgehoben. Die Hinweiszeile nennt die Zahl, und der Spieler wählt selbst (*Annahme*: kein Spielen für den Spieler).

Angezeigte Zahl = wirkende Zahl bleibt gewahrt: Der Preis ist der Preis, und der Fortschritt steht als zweite Zahl daneben.

---

## 7. Vergleich mit Variante B unter denselben Randbedingungen

B unter E5 heißt: mehrere Optionen, jede einmal je Sol, Summe ≤ 4 AP. Das ginge z. B. mit 1 + 3 oder mit 1 + 2 (3) bei Cantina 1.

| | C (ein Gespräch, Länge wählbar) | B (jede Option einmal, Summe ≤ 4) |
|---|---|---|
| Tempo bei gleichem Tagesmaximum | gleich | gleich (Tagesmaximum je Sol ist dasselbe) |
| Klicks je Figur und Sol | 1 | 2–3 |
| Entscheidung | eine klare (wie viel heute?) | keine echte: alle Optionen nehmen ist fast immer richtig |
| Stufengrenze mit Verfall (E4) | jeder Rest genau treffbar | Reihenfolge-Rätsel: Eine falsch gewählte Reihenfolge lässt etwas verfallen |
| Erzählung | ein Abend, eine Länge | mehrere Momente am selben Abend |
| Ausbau | längeres Gespräch | weitere Option, bei Summe ≤ 4 kaum Spielraum |

**Ergebnis:** Unter dem 4-AP-Deckel bringt B gegenüber C kein Tempo, aber mehr Klicks und eine Verfallsfalle. Das Tempo, das der Owner bei B vermisst hat, hängt an den Stufenkosten (§5), nicht am Optionsmodell.

---

## 8. Config-Vorschlag (Vorschlag; Quelle bleibt `config/path_depth.php`, Vorbehalt oben)

```php
'levelup_ap' => [
    'mission_family' => [1 => 6, 2 => 9, 3 => 12, 4 => 16, 5 => 20],
    // PROPOSAL 2026-11-13 (variant C): 20 AP to the top level, see spec §5.
    'character' => [1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6],
],

// Conversation length key => AP spent. One conversation per character and Sol; the
// longest open length is the daily maximum, so no separate cap is needed.
'conversation_options' => ['short' => 1, 'normal' => 2, 'long' => 3, 'extended' => 4],

// Cantina level each length needs: one more length per expansion (max 2 / 3 / 4 AP).
'conversation_option_cantina_level' => ['short' => 1, 'normal' => 1, 'long' => 2, 'extended' => 3],

// One conversation per character and Sol (new key).
'conversations_per_sol' => 1,

// Rule off: the daily maximum follows from the longest open length.
'conversation_ap_cap_per_sol' => ['base' => null],
```

Folgen außerhalb der Config (nur benannt):
- **Code:** „Heute schon gesprochen“ je Figur merken. Die Kürzung `min(cost, toNextLevel)` entfällt (E3). Der Reset auf 0 beim Aufstieg bleibt (E2), und das Ergebnis meldet die verfallenen AP. Die API liefert je Länge `wasted` (verfallende AP) für die Warnung. `requiredCantinaLevel()` mit den neuen Werten prüfen. TDD wie üblich.
- **Content:** Namenspool für `extended`, Figurenzeilen nach Gesprächslänge für den Zustand „heute fertig“, Hinweiszeile und Warnchip, Ergebniszeile mit Verfall.
- **Tomas:** Seine Bonusstaffel (`game.bartender.ap_bonus_by_relationship_level`) bleibt an den Stufen, mit schnelleren Stufen prüfen (Balance-Hinweis §5).
- **GDD §4b**, GLOSSAR „Gespräch“ („ein Gespräch je Figur und Sol, Länge wählbar“), `docs/game-reference.md`: nach Entscheidung.
- **`game:reset-player`-Szenarien:** prüfen, ob sie Beziehungsstufen setzen (nicht geprüft).

---

## 9. Owner-Fragen — entschieden 2026-11-13 (Zahlen siehe AP-Skala)

1. **Variante C übernehmen?** Empfehlung **ja**. Sie bringt dasselbe Tempo wie B mit einem Klick statt mehreren, eine echte Entscheidung je Sol und keine Verfallsfalle (§7).
2. **Cantina-Stufe verlängert das Gespräch (2 / 3 / 4 AP)?** Empfehlung **ja**. Der Ausbau wirkt dann sichtbar, und die Reihe endet genau beim 4-AP-Deckel. Die Alternative C′ (alle Längen sofort) wäre einfacher, nimmt dem Ausbau aber die Wirkung auf die Beziehungen.
3. **Stufenkosten auf 2 / 3 / 4 / 5 / 6 senken?** Empfehlung **ja, als Platzhalter**. Das trifft das Ziel-Tempo (bekannt 2–3 Sol, brüderlich ≈ 7 Sol mit Cantina 3) und ist mit voll ausgebauter Cantina so schnell wie heute.
4. **Ziel-Tempo wie in §5?** Empfehlung **ja**. Ist es dir zu langsam, ist der Hebel ausschließlich die Stufenkosten.
5. **Warnung als Chip am Knopf plus Hinweiszeile, ohne Bestätigungsdialog?** Empfehlung **ja**. Das ist transparent, ohne Reibung.
6. **Bei einer globalen AP-Neukonzipierung das Tempo in Sol festhalten und die AP-Werte mitskalieren?** Empfehlung **ja**.
