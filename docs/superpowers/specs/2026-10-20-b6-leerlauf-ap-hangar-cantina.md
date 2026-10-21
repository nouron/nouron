# B6 — Leerlauf-AP bei Hangar-First und Cantina-First (Phase 1)

**Stand:** 2026-10-20 · **Autor:** game-designer (Konzept, keine Code-/Config-Änderung) · **Bezug:** Handtest 2, T30 (`2026-10-05-t30-gleichwertige-eroeffnungen.md`), Pacing (`2026-10-16-phase1-pacing-entscheidungen.md`)

Kennzeichnung: **gemessen** = Bot-Baseline 2026-10-20 (140 Läufe, Profil `default`, Reports `storage/logs/playtest/`). **gerechnet** = eigene Rechnung. **Annahme** = meine Deutung, keine Owner-Entscheidung. Zahlen stehen nur in Tabellen und Hypothesen, der Prosa-Teil ist zahlenfrei (ADR 0004).

## 1. Ist das ein Designproblem?

| Eröffnung | Phase-1-Ende Sol (min/Median/max) | Leerlauf-AP Phase 1 (Median) | Leerlauf-AP letzte 3 Sol (Median) |
|---|---|---|---|
| Labor | 10 / 13 / 13 | 1 | 0 |
| Hangar | 10 / 13 / 13 | 60 | 28 |
| Cantina | 10 / 11 / 12 | 7,5 | 5,5 (Max 26) |

Alle drei gemessen. Befunde:
- **Das Tempo ist kein Problem.** Hangar und Labor enden im Median am selben Sol. Der Leerlauf kostet Hangar-First keine Zeit, nur Sinn: Phase 1 ist Regolith-gebunden (gemessen), die AP haben keine Rg-freie Senke.
- **Cantina ist kein Problem.** Median in den letzten Sols nahe null; der Ausreißer (Max 26) ist Zufallsereignis-Streuung. **Annahme:** Cantina-First braucht keine eigene Senke.
- **Hangar ist ein kleines Designproblem.** Der Leerlauf ist am größten zwischen Sol 8 und 10 (gemessen 14–16 AP je Sol), also nach dem Aufdecken der Karte. Die Erkundung (gemessen ~45 AP je Lauf, in allen Eröffnungen gleich) ist hier aufgebraucht; Fundflüge und Scans kosten nur wenige AP je Fund (Config: Scan plus ein Flug-AP, Fund-Pool fest und klein). **Annahme (gerechnet):** Selbst alle Funde zusammen decken nur einen Bruchteil der 60 Leerlauf-AP.
- **Der Hinweis führt in die Irre (Code gelesen, `OnboardingHintService::bestRemainingApPool`).** Ohne Labor und ohne bezahlbares Nebelfeld und ohne Cantina fällt der Hinweis "AP übrig" auf *Bau* zurück, auch wenn der Bau Regolith-gebunden ist oder der Deckel des Ziels erreicht ist. Das passt zu "wusste nicht wohin mit AP". Das ist ein Transparenz-Mangel, der unabhängig von jeder neuen Mechanik behebbar ist.

**Antwort auf Frage 1:** Beides. Transparenz allein reicht nicht, weil es nach dem Kartenende schlicht keine Senke gibt; ein Hinweis kann nichts anbieten, was nicht existiert. Aber der Hinweis ist zuerst zu reparieren, weil er heute aktiv falsch lenkt. Laut Owner ist Pfad-Parität hartes Kriterium; ein Median von 60 gegen 1 Leerlauf-AP ist ein Paritätsbruch in der Erlebnisqualität, nicht im Tempo.

## 2. Varianten (je genau ein Hebel)

### V0 — Hinweis ehrlich machen (Transparenz, keine neue Mechanik)
**Hebel:** Der Rest-AP-Hinweis erkennt "Regolith fehlt" und "Deckel erreicht" und nennt stattdessen die tatsächlich verfügbaren Rg-freien Optionen des Pfads (Fundflug, Scan, Reparatur, Schiff warten) beziehungsweise sagt offen, dass nichts Sinnvolles übrig ist und der Sol beendet werden kann.
**Parität:** neutral, Labor-First sieht den Hinweis praktisch nie. **Risiko:** gering (nur Text und Bedingung, kein Bot-Einfluss; der Bot ignoriert Hinweise). **Wirkung (Hypothese):** Tabelle oben unverändert; erwartet wird nur im Handtest weniger Ratlosigkeit. Nicht per Bot messbar.

### V1 — Owner-Idee B5: rudimentärer Techtree plus erste Kenntnis vorab (Owner-Idee, nicht entschieden)
**Hebel:** Genau eine Einstiegskenntnis (Stufe 1) ist ohne Labor erforschbar, mit dem im Hangar-First-Weg ohnehin vorhandenen Techtree-Screen. Alles weitere (andere Kenntnisse, höhere Stufen, Kenntnis-Zuweisung an Berater, Daten-Sweep-Einlagen) bleibt am Labor.
**Wirkung (Hypothese, gerechnet):** Eine Stufe-1-Kenntnis kostet nach T30-Stand 20 AP. Hangar-Leerlauf in Phase 1 sinkt von 60 auf etwa 40, die Sols 8–10 werden entlastet, die letzten 3 Sols (28) bleiben etwa gleich. Phase-1-Ende unverändert, sofern die Kenntnis keinen Rg-Ertrag bringt (also nicht Geologie; **Annahme:** Bau- oder Agrarkenntnis).
**Pfad-Paritäts-Konflikt (Annahme):**
1. Nach CLAUDE.md und Glossar kommen *alle* Kenntnisse über das Labor. V1 bricht diese klare Regel; die Frage "wofür brauche ich das Labor" bekommt eine schwächere Antwort in Phase 1.
2. Bleibende Gründe fürs Labor: alle übrigen Kenntnisse, Stufen ab 2, Geologie (Rg-Ertrag, braucht Labor Lv2), Kartografie (Labor plus Hangar), Daten-Sweep, Berater-Zuweisung. Phase 1 *verlangt* das Labor aber nicht (Pfadgebäude zählen beliebig), also wäre die Antwort ab Sol 10 dieselbe wie heute.
3. Die Senke ist pfadneutral (auch Cantina-First). Das ist ein Teil von E3 (universeller AP-Abnehmer) aus T30, dort ausdrücklich nur als Rückfallebene vorgesehen, weil er Pfadidentität einebnet.
4. Mit nur einer Stufe ist die Senke endlich (20 AP) und löst Sol 11–13 nicht.
**Risiko:** echte Balance-Frage, kein Bot-Fehler. Bot müsste die Regel kennen, sonst Messung wertlos (offene Variable).

### V2 — Hangar-eigenes Forschungs-Äquivalent ("Flugprogramm")
**Hebel:** Eine Rg-freie, dauerhafte AP-Senke, die am Hangar hängt (nicht am Labor): wenige Stufen, in die Leerlauf-AP investiert werden; Wirkung nur auf Hangar-Themen (zum Beispiel Missions-Erfolgschance oder Organika-Kosten der Flüge), nicht auf Regolith und nicht auf Grundwerte der Kolonie. Das beantwortet die offene Frage aus dem Pacing-Dokument ("brauchen Hangar/Cantina ein Forschungs-Äquivalent?") für den Hangar.
**Wirkung (Hypothese, gerechnet, Stufenkosten sind Vorschlag):** Zwei Stufen zu je etwa 15 AP absorbieren etwa 30 der 60 Leerlauf-AP, die letzten 3 Sols von 28 auf etwa 14. Phase-1-Ende unverändert (kein Rg-Ertrag).
**Parität:** wahrt die Identität; Labor behält Forschung, Hangar bekommt ein eigenes Gegenstück. Kein Eingriff in Labor-First. **Risiko:** größter Entwurfsaufwand (neue Mechanik, UI, GDD, Bot-Regel); die Gefahr, ein Pflichtprogramm zu schaffen (Hangar-Pfad "muss" es kaufen), ist per Wirkungsauswahl zu begrenzen. Cantina bekäme nur bei gemessenem Bedarf ein Gegenstück.

### V3 — Fund-Pool wächst mit Flügen (Fernortung)
**Hebel:** Jeder abgeschlossene Erkundungsflug deckt zusätzlich ein neues verstecktes Signal auf (Fund-Pool nicht mehr fest).
**Wirkung (Hypothese, gerechnet):** Jeder Fund bindet nur wenige AP (Scan plus Flug), absorbiert also höchstens etwa 10–20 AP, bringt aber Regolith. **Annahme:** Das beschleunigt Phase 1 (etwa ein Sol früher) und widerspricht der Vorgabe, Zeit aus einer Nicht-Rg-Bremse zu holen. Zusätzlich E3-Charakter (AP wird zu Rg). Nicht empfohlen, hier nur dokumentiert.

## 3. Empfehlung (Annahme)

1. **Jetzt V0.** Kleinster Hebel, behebt den aktiv falschen Hinweis, kostet keine Pfad-Identität.
2. **Danach Messung, dann V2 statt V1**, falls der Hangar-Leerlauf in den Sols 8–10 nach V0 im Handtest weiter stört. Begründung: V2 antwortet auf die Paritätsfrage mit Eigenständigkeit (jedes Pfadgebäude eigene Senke), V1 antwortet mit Angleichung (jedes Gebäude darf forschen) und hebt die Alleinstellung des Labors auf.
3. V1 bleibt als billige Alternative, falls der Owner Aufwand scheut: Der Konflikt ist handhabbar, wenn die Vorab-Kenntnis bewusst schwach (kein Rg, keine Bau-Beschleunigung der Pfadgebäude) gewählt wird und das Labor weiterhin alle Zuweisungs- und Mehrstufen-Vorteile exklusiv behält. V3 verwerfen.
4. Cantina-First: keine Maßnahme (gemessener Median gering). Erst bei Handtest-Befund neu bewerten.

## 4. Offene Variablen

- **Bot-Abhängigkeit:** Der Bot erkundet in allen Eröffnungen gleich viel; ein Mensch erkundet vermutlich langsamer oder schneller. Der gemessene Leerlauf ist also bot-bedingt, nur die Richtung (Hangar-Leerlauf deutlich höher) ist belastbar. Bei V1/V2 braucht der Bot eine Kaufregel, sonst misst man 0 Wirkung.
- Ob Hangar-Leerlauf vor allem Sols 8–10 oder 10–13 trifft (Bot-Reihenfolge der Missionen, Ausbauten).
- Ob die Geschenk-Drohne (T30) und der Rg-Weg über Fundflüge schon in den Messwerten wirken.

## 5. Gehört nicht dazu

- Keine Änderung der Rg-Kosten (Bau, CC, Uplink-Station), der Phase-1-Kriterien oder der Grund-AP.
- Kein AP-Deckel für Pilot/Konsul in diesem Schritt (bleibt vorbereitet, Entscheidung offen); kein Kenntnis-Deckel.
- Keine Kürzung der Labor-Forschung. Labor-First ist kein Maßstab, der abgesenkt wird.
- Kein AP-zu-Regolith-Wechselkurs (E3), keine Flotten-/Karten-Mechanik.
- Erkundungsflüge und der Rg-bedingte Erkundungsdrang bleiben unangetastet (im Handtest bestätigt gut).
- Kein Screen-Layout des neuen Canvas-Techtree; hier zählt nur die Spielmechanik.

## 6. Owner-Fragen (mit empfohlener Antwort)

1. V0 (ehrlicher Rest-AP-Hinweis) sofort umsetzen? **Empfehlung: ja.**
2. Soll Hangar ein eigenes Forschungs-Äquivalent bekommen (V2) statt eines geöffneten Labors (V1)? **Empfehlung: V2, aber erst nach V0-Handtest.**
3. Falls V1 gewünscht: Darf "alle Kenntnisse über das Labor" aufgeweicht werden, und welche eine Kenntnis? **Empfehlung: nur wenn V2 abgelehnt wird; dann eine Kenntnis ohne Rg-Wirkung.**
4. Cantina-First ohne eigene Senke lassen? **Empfehlung: ja, bis ein Handtest Leerlauf zeigt.**
5. Zielwert für Hangar-Leerlauf (Median Phase 1 gegenüber Labor)? **Empfehlung: nicht über etwa die Hälfte des heutigen Wertes, letzte 3 Sols unter etwa 15 AP.**
