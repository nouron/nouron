# Nacheichung der Run-Schwierigkeit: Hebel-Optionen (2026-11-26)

**Änderung 2026-11-27 (Owner):** Option A wird in dieser Form nicht umgesetzt. Statt Zielwerten je Eröffnung kommen eigene Ziele je Eröffnung: `2026-11-27-eroeffnungsziele.md`.

**Status:** Vorschlag zur Owner-Wahl. Keine Config, kein Code geändert. Danach wird **eine** Änderung gemessen (Messung **M7**; die in Spec 2026-11-25 §9 „M6“ genannte Fokus-Messung rückt damit nach, der Name M6 ist vergeben).

## 1. Ausgangslage (gemessen M6, `storage/logs/playtest/20261126_m6/`, 72 Läufe)

| Profil | Siege | Median Sieg-Sol | Cantina / Hangar / Labor |
|---|---|---|---|
| `aimless` (ignoriert Ziele) | 18/24 | 75 | 7/8 · 5/8 · 6/8 |
| `default` (gelegentlich) | 20/24 | 73 | 7/8 · 7/8 · 6/8 |
| `focus` (gezielt) | 22/24 | 74,5 | 7/8 · 8/8 · 7/8 |

Soll: ungezielt rund 50 %, Sieg etwa Sol 90+; gezielt Sol 70–85 (GDD §15). Das Problem sind **zu viele und zu frühe** Siege ohne Zielspiel.

**Erfüllungs-Sole der mittelfristigen Ziele (aus den M6-Berichten ausgelesen):**

| Ziel | `aimless` | `focus` | Abstand gezielt/ungezielt |
|---|---|---|---|
| Expeditionen | 12/12; Hangar Sol 28–33, Labor 41–62, Cantina 42–66 | 10/12; Hangar 31–48 | keiner |
| Handelspartner | 9/12; Cantina Sol 30–49, sonst 57–84 | 8/12 | keiner |
| Kreditreserve | 3/6; Sol 60–64 | 6/6; Sol 40–62 | klar |
| Forschungsvorsprung | 1/6; Sol 75 | 6/6; Sol 62–68 | klar |

**Grenzen:**
- 8 Seeds sind nur 8 Ziehungen. Expeditionen und Handelspartner stecken in je 4, Expertenstab in 6 (18 von 24 Läufen je Profil). Bei 24 Läufen je Profil ist ein Lauf rund 4 Prozentpunkte, die Zufallsstreuung liegt grob bei ±20 Punkten (*Annahme, gerechnet*).
- Ein Lauf endet beim zweiten Ziel. Das dritte bleibt unbeobachtet: Eine neue Siegregel lässt sich aus den Logs **nicht** nachrechnen, „erfüllt/gezogen“ ist kein Schwierigkeits-Ranking.
- **Offene Bot-Variable:** Bei Expeditionen, Handelspartner und Expertenstab spielt `focus` nicht schneller als `aimless`. Unklar, ob das Spiel dort nichts zu beschleunigen bietet oder der Bot Missionen und Einkäufe nicht gezielt vorzieht. Für diese Ziele ist **nicht belegt**, dass ein Hebel ungezieltes Spiel stärker trifft.

## 2. Option A: Nebenbei-Ziele anheben (Zielwerte, mit Zuschlag je Eröffnung)

- **Änderung** (alle Werte *Annahme*): Expeditionen 10 → 14, mit Hangar-Eröffnung 18. Handelspartner 8 → 10, mit Cantina-Eröffnung 14. Kreditreserve bleibt (sie trennt schon). Gilt als ein Hebel „Fokus-Zielwerte“, weil jedes Ziel einen eigenen Erfüllungs-Sol hat.
- **Warum:** Diese Ziele fallen heute in allen Eröffnungen ohne Zielspiel weit vor Sol 90. Nur den Zuschlag (P5-A wie in der Spec) zu setzen, ließe Expeditionen bei Labor und Cantina bei Sol 41–66.
- **Gezielt gegen ungezielt:** Hoffnung, dass gezieltes Spiel nachziehen kann; wegen der Bot-Variable offen. Erwartbar wird `focus` mitgebremst.
- **Nebenwirkungen:** **Paritätsrisiko Handelspartner:** Ohne Cantina blieb `aimless` in 3 Läufen schon bei 7 stehen, `focus` brauchte dort für 8 Sol 62–74; 10 kann auch gezielt „nie“ heißen (zwei von drei Eröffnungen). Die Zähler stehen nach Erfüllung weitgehend still, die Logs zeigen keine Obergrenze: 14 und 18 sind reine Annahmen. Die Aufgabenkarte zeigt je Eröffnung andere Zahlen (kurzer Erklärtext). Owner-Bedingung prüfen: vorher ohne Bot zählen, ob in Phase 2 genug Missionen möglich sind.
- **Aufwand:** klein bis mittel (Feld `opening_target` mit Test, Texte, `game-reference.md`).
- **Hypothese gesamt (*Annahme*):** `aimless` 11–15/24, Median Sieg-Sol ≥ 80; `default` 15–18/24; `focus` ≥ 18/24, Median ≤ 85.
- **Hypothese je Ziel (*Annahme*, Median Erfüllungs-Sol `aimless`; „nie“ zählt als spät):** Expeditionen mit Hangar ≥ 50, sonst ≥ 75. Handelspartner mit Cantina ≥ 55, sonst ≥ 80. `focus` erfüllt jedes der beiden je Eröffnung in mindestens der Hälfte der gezogenen Läufe bis Sol 85. Jede Zahl ist einzeln widerlegbar.

## 3. Option B: Ziehungsregel „ein mittelfristiges, zwei langfristige“

- **Änderung:** Statt „mindestens je eines“ immer genau 1 + 2. Zielwerte bleiben. Die Owner-Vorgabe vom 2026-11-24 bleibt erfüllt.
- **Warum:** Zwei Nebenbei-Ziele in einer Ziehung entfallen. In M6 (Teilmengen) gewann `aimless` bei den Ziehungen mit zwei mittelfristigen Zielen (Seeds 1, 4, 6, 8) 10/12, bei den anderen 8/12; `default` 11/12 gegen 9/12, `focus` 12/12 gegen 10/12. Das liegt innerhalb der Streuung; belegt ist eher eine Wirkung auf den Sieg-Zeitpunkt (schnelle Siege, Sol 42–70, vor allem aus den Seeds 4, 6, 8) als auf die Quote.
- **Gezielt gegen ungezielt:** In diesen Teilmengen trifft es alle drei Profile gleich (je 2 Läufe). Allein erreicht der Hebel die 50 % vermutlich nicht (*Annahme*: um 67 %).
- **Nebenwirkungen:** Konflikt mit der Design-Absicht: Die Ziehung mit zwei mittelfristigen Zielen ist laut GDD §15 und Spec 2026-11-25 §1.1 der „sehr gute Run mit Glück“ und entfiele. Expertenstab wird noch häufiger (reines Zeitziel), mehr Warten und Ziehungsglück, weniger Run-Varianz. Eröffnungen bekommen seltener „ihr“ Fokus-Ziel. Seeds ziehen andere Ziele, Vergleich mit M6 nur über Quoten.
- **Aufwand:** klein (Auswahlregel mit Test).
- **Hypothese und Abbruch (*Annahme*):** `aimless` 13–17/24, Median ≥ 80; `focus` 18–21/24. Rücknahme bei Verletzung der `focus`- oder Eröffnungs-Grenze aus §6; wirkungslos, wenn der `aimless`-Median nicht mindestens 5 Sole später liegt.

## 4. Option C: Etappen ohne Wahl (Zielbild, jetzt nicht empfohlen)

- **Änderung:** Drei Auflagen nacheinander (mittel, mittel, lang), Sieg mit der dritten; Prüftermine ersetzen die Nexus-Leiter (Entwurf 2026-11-26 B.1 d).
- **Warum:** Fortschritt am Langläufer beginnt erst spät, Siege rutschen nach hinten. Ob gezielt davon profitiert, hängt an Zählbeginn und Bot.
- **Nebenwirkungen:** Bester Spannungsbogen; aber „2 von 3“ entfällt, eine harte Auflage kann blockieren (Tausch-Regel nötig). Fokus-Auflagen müssen je Eröffnung fair sein.
- **Aufwand:** mittel bis hoch (Zustand, UI, Sol-Bericht, Bot).
- **Hypothese und Abbruch:** bewusst keine; C gehört nicht in M7 und ist erst nach A oder B sinnvoll zu eichen.

## 5. Verworfen: Frist verkürzen (100 → 85)

Rein abgeschnitten an den M6-Sieg-Solen (*Annahme*: Bot spielt gleich, was nicht gilt, er nutzt die Frist in der Bewertung): `aimless` 12/24, `default` 17/24, `focus` 18/24. Die Quoten sähen passend aus, aber: ungezielte Siege ab Sol 90 wären unmöglich (Widerspruch zur Kalibrierregel und zum Korridor „realistisch 85–95“), `aimless` mit Hangar fiele auf 2/8 (Labor 4/8, Cantina 6/8), und T61 käme später noch als Druck dazu.

## 6. Empfehlung und Abbruch

**Option A** als M7, weil sie an den gemessenen Ursachen der frühen Siege ansetzt. Batch wie M6 (3 Profile × 3 Eröffnungen × Seeds 1–8), eine Änderung; Dauerschätzung und Owner-Freigabe vorher. Davor ohne Bot: möglicher Missions- und Einkaufsumfang bis Sol 90, und im Bot-Code klären, ob `focus` diese Ziele vorziehen kann.

**Abbruch / Rücknahme** (alle Schwellen *Annahme*), wenn eines gilt:
- `focus` unter 17/24 Siegen oder Median Sieg-Sol über 85.
- Eröffnungen (alle Profile zusammen, je 24 Läufe) mehr als 15 Prozentpunkte auseinander (M6 Siege: Cantina 21, Hangar 20, Labor 19 von 24).
- `aimless` weiter über 16/24 (Hebel wirkungslos) oder unter 8/24 (zu hart).

Dass A die Profile stärker **trennt**, wird nicht erwartet (§1). Bewertet wird nur, ob `aimless` später siegt, ohne `focus` zu brechen; sonst Bot oder Zielart prüfen, nicht weiter an Zahlen drehen.

## 7. Offene Owner-Fragen

1. Welche Option wird als M7 gemessen?
2. Gilt „ungezielt ~50 %“ für `aimless`, und darf `default` (gelegentlich) näher an 70 % liegen?
3. Bei A: Sind je Eröffnung verschiedene Zielzahlen auf der Aufgabenkarte in Ordnung?

## 8. Owner-Entscheidung (2026-11-26)

- Option **A**, nach der Vorprüfung ohne Bot (§6).
- „Ungezielt rund 50 %“ gilt für `aimless`; `default` darf näher an 70 % liegen.
- Zielzahlen dürfen je Eröffnung verschieden auf der Aufgabenkarte stehen, mit kurzem Erklärtext.
