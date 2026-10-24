# Pfad-Vertiefung: Vertrautheit (Cantina) und Missionserfahrung (Hangar) neben den Kenntnissen (Labor)

Stand 2026-10-24 · Autor: game-designer · Status: Entwurf, Owner-Fragen offen (Abschnitt 4b)
GDD: §4b „Vertiefung: dieselbe Struktur in allen drei Pfadgebäuden“

## 1. Idee (Owner-Skizze)

Jedes Pfadgebäude hat mehrere Dinge, jedes Ding hat fünf Stufen. Labor: 7 Kenntnisse. Cantina: Beziehung zu Figuren. Hangar: Missionsfamilien. Die Skizze ist ein **Strukturmodell**, kein Bildschirm. Jede Oberfläche zeigt den Fortschritt in eigener Form (Techtree, Balken an der Figur, Balken an der Missionsfamilie).

## 2. Entschiedene Punkte

- **Aufstieg überall per AP-Investition, nicht per Nutzung** (Owner 2026-10-24, wörtlich bestätigt). Labor: Kenntnisstufen (wie bisher). Hangar: Missionsstufen je Familie. Cantina: Vertrautheitsstufen („Gesprächslevel“) je Figur. Das ersetzt die Entscheidung vom 2026-10-23 („Cantina/Hangar per Nutzung“) und kippt F1.
- B6/V2 „Ausbildung/Training“ geht in diese Spec (T41) auf; V2 ist kein eigener Schritt mehr.
- Stufen gelten pro Lauf. Kodex bleibt dauerhaft.
- Cantina-Figurenvielfalt bleibt vollständig (15 Figuren). Pro-Figur-Wirkungen sind ein **eigener Design-Schritt**.
- Sichtbarkeit: Balken an der Figur und an der Missionsfamilie, mit nächster Schwelle.
- Gebäudestufe deckelt die Maximalstufe (F5, Version 1 nur Gebäudestufe).
- Berater-Außendienst und -Burnout sind zurückgestellt (`docs/gdd/archiv-berater-aussendienst-burnout.md`).

## 3. Bewertung

**Passt:** Alle drei Pfade teilen dieselbe, leicht lernbare Regel („AP in eine Stufe stecken“). Die Leerlauf-AP im Hangar (Hangar-First, ca. 60 ungenutzte AP in Phase 1, gemessen 2026-10-20) bekommen eine Senke. Barkeeper (4 Stufen) und Kodex sind schon Mini-Instanzen derselben Bauart.

**Entfallene Risiken:**
- ~~Risiko 1 (keine Leerlauf-AP-Senke)~~: entfällt, die Investition ist die Senke.
- ~~Risiko 3 (Cantina-Zufall)~~: entschärft. Wer investiert, braucht keinen Zufallsauftritt als Zähler. Rest siehe Frage O1.

**Bleibende Risiken:**
1. **AP-Konkurrenz:** Ausbau und Ausführung ziehen am selben AP-Pool. Aus „Leerlauf“ kann Dauerdruck werden. Siehe O3.
2. **Überschneidung:** Raumfahrer-Rang und Kenntnis-Level wirken schon auf Erfolgschance und Proviant. Eine Stufe mit Prozentbonus wäre ein dritter Stapel (Transparenz-Regel). Daher F2: Stufe ersetzt die Variante, zeigt echte Zahlen.
3. **Kodex-Doppelzählung:** Kodex zählt dauerhaft über Läufe (Begegnungen), Vertrautheit pro Lauf (AP). Mit dem neuen Aufstieg sind es jetzt **verschiedene Auslöser**, die Verwechslung sinkt (F3).
4. **Gebäudestufen:** Cantina und Hangar haben höchstens Stufe 3 (§4c), die Vertiefung hat 5 Stufen. Gelöst durch F5.
5. **Credits-Inflation (R2):** Missions-Credits sind verdoppelt (2026-09-30). Stufen-Ertrag muss klein bleiben, vor Freigabe messen.

## 4. Entscheidungen F1–F7 im neuen Modell

| # | Status | Inhalt |
|---|---|---|
| F1 | **gekippt, ersetzt** | Frühere Entscheidung: keine freiwillige AP-Investition in Version 1. Neu: AP-Investition ist der einzige Aufstiegsweg in allen drei Pfaden. |
| F2 | bleibt | Stufe schaltet je Missionsfamilie eine lukrativere Variante frei, die die alte **ersetzt** (kein Prozentbonus, keine Wahl zwischen Varianten). Entscheidung liegt in Breite gegen Tiefe über Familien. |
| F3 | bleibt | Kodex und Vertrautheit getrennt: Kodex zählt Begegnungen über alle Läufe, Vertrautheit ist die AP-Stufe im Lauf. Keine gemeinsamen Auslöser mehr. |
| F4 | **entschärft** | Zufallsfiguren-Steuerbarkeit: Investieren hängt nicht an Auftritten. Offen nur, ob eine Figur zuvor erschienen sein muss (O1). |
| F5 | bleibt | Version 1 nur Gebäudestufe als Deckel. Berater-Rang-Gate später, nur für Stufe 5, für alle drei Pfade zugleich (R5 unverändert, Konsul-Punkt offen). Annahme Zuordnung: Stufe 1–2 ab Gebäudestufe 1, 3–4 ab 2, 5 ab 3. |
| F6 | bleibt | 5 Missionsfamilien: Erkundung, Bergung, Versorgung, Handel und Kurier, Schutz (Tabelle unten). |
| F7 | **entfällt** | „Fehlschläge zählen weniger“: es gibt keinen Nutzungszähler mehr. |

**R2 — Folgen von „ersetzt“ (unverändert gültig).** (1) Ertrag steigt mit der Stufe für jede Mission der Familie, sichtbar als benannte Variante („Erkundungsflug II“); der Missionsdialog zeigt die echten Zahlen. (2) Credits-Inflation: Steigerung klein, vor Freigabe messen. (3) Proviant leicht mitwachsen lassen, sonst wird der Organika-Hebel entwertet. (4) Leicht/normal/schwer bleibt innerhalb jeder Variante. (5) Eine Stufenformel je Mission statt ~20 Parallel-Missionen. Kein Regolith-Ertrag (T31).

**R3 — Kodex getrennt.** Kodex = Sammlung über alle Läufe, Vertrautheit = Stand im Lauf. UI benennt den Unterschied („in diesem Lauf“ gegen „insgesamt“).

**R5 — Berater-Rang als Gate für Stufe 5:** unverändert zurückgestellt; Empfehlung: nur Fortschritts-Gate, alle drei Pfade gleichzeitig; Konsul-Regel (§12) wäre vorher zu klären.

**F6 — 5 Missionsfamilien** (Annahme; Zuschnitt folgt den Grundbedürfnissen aus §4b):

| Familie | Missionen | Profil |
|---|---|---|
| Erkundung | Erkundungsflug, Datensammelflug, Fernexpedition | Information, AP-Belohnung für Kenntnisse |
| Bergung | Bergungsflug, Trümmerbergung, Ruinen-Expedition, Harvester-Bergung | Funde, Werkstoffe (Ruinen können durch T31 wegfallen) |
| Versorgung | Versorgungsfahrt, Hilfsgütertransport | Organika, Vertrauen |
| Handel und Kurier | Botenflug, Handelsfahrt | Credits |
| Schutz | Konvoi-Begleitung, Umkreis-Patrouille | Korvetten-Einsatz |

Eine Familie kann Schiffe mischen; die Erfahrung gehört der Kolonie, nicht dem Schiff.

### 4a. Heutiger Stand der AP-Mechanik (gelesen, `config/game.php`, `config/missions.php`)

- Es gibt nur einen gemeinsamen AP-Pool (GDD §13.1, Entscheidung 2026-08-02); AP-Arten existieren nicht mehr (Code-Reste gesondert bereinigen).
- AP-Deckel (T35): `ap_cap_per_sol` begrenzt **nur Gebäude-Ausbau-AP je Gebäude und Sol** (Basis plus Baumeister-Bonus nach Rang); Reparatur und Platzierung sind ausgenommen. Er gilt nicht für Kenntnis-Investition.
- Kenntnis-Investition läuft über `ResearchService::invest()`; zweckgebundene Bonus-AP über `investBonus()` (Tomas, Datensammelflug).
- Hangar-Missionen kosten AP, Organika und Verschleiß; Training und Mission ziehen am selben Pool.

### 4a1. Ausbau gegen Ausführung (Owner 2026-10-24)

| | Labor | Hangar | Cantina |
|---|---|---|---|
| Ausbau (Stufenaufstieg) | Kenntnis: nur AP | Missionserfahrung je Familie: nur AP | Gesprächslevel je Figur: nur AP, erst nach erstem Treffen im Lauf |
| Ausführung (unverändert) | Kenntnis wirkt | Mission: AP + Organika + Verschleiß | Gespräch: AP; Handel: AP + Rohstoffe |
| Stufe wirkt auf | Kenntnis-Effekt | Ertrag der Familie (F2), nicht Kosten | später (O4) |

Ausbau und Ausführung teilen sich den AP-Pool und konkurrieren darum; das ist die Senke für Leerlauf-AP (Hangar-First).

**Entschieden (Owner 2026-10-24, Variante C):** Version 1 ist reine AP-Investition ohne Fortschritt aus der Ausführung. Rahmung: Hangar = „Training“ (Simulator, Übungsflüge; Label Missionserfahrung/Training bleibt Vorschlag), Cantina = Beziehung pflegen. Das Spiel erklärt, dass man ohne Einsatz üben und jemanden kennenlernen kann. Begründung: Hangar-First hat in Phase 1 wenig Missionen, aber viele freie AP; nur der AP-Weg gibt dort eine Senke. Variante B (Ausführung als Voraussetzung) ist verworfen, weil sie genau das verhindert.

**Bewusst offene Erweiterung (Variante A, nicht Version 1, nicht entschieden):** Ausgeführte Missionen oder Handel könnten später zusätzlich einen kleinen Fortschritt am selben Balken geben. Hinweise: Der Balken muss so gebaut sein, dass ein zweiter Beitrag ohne Modelländerung hinzukommt; Quellen im Balken einzeln ausweisen (Transparenz-Regel); es bringt den Zähler zurück (frühere F7 „Fehlschläge zählen weniger“).

### 4b. Offene Designfragen (Annahmen mit Empfehlung, keine Owner-Entscheidung)

| # | Frage | Annahme / Empfehlung |
|---|---|---|
| O1 | **ENTSCHIEDEN (Owner 2026-10-24): ja.** Cantina-Investition erst nach dem ersten Treffen der Figur im Lauf. | Folge: Zuverlässige Gäste (Tomas, Barkeeper, Corvan) sind früh investierbar; Zufallsfiguren bleiben Glück beim Zugang, nicht beim Fortschritt. |
| O2 | Steigen die Kosten je Stufe? | Annahme, Empfehlung ja: progressiv wie Gebäudestufen, damit Breite gegen Tiefe eine echte Wahl bleibt. Kurve nach Messung, Config. |
| O3 | Gestrichen. | Es gibt nur einen AP-Pool. Offen bleibt nur, ob der T35-Deckel (gilt nur für Gebäude-Ausbau) ein Gegenstück je Ding braucht; Annahme: nein, es sei denn die Messung zeigt Ein-Sol-Durchsprints. |
| O4 | Stufenwirkung Cantina? | Eigener Design-Schritt (je `game_role`). Version 1 zeigt Balken und Schwellen, ggf. Kodex-Zugang. |
| O5 | Credits-Inflation R2? | Bleibt: Stufen-Ertrag klein, messen. Zusätzlich neu: Investitionen sind eine AP-Senke, die Ertragssteigerung muss ihre AP-Kosten tragen. |
| O6 | Bleibt Hangar-Wirkung rein Ertrag, oder auch Zugang? | Annahme: Ertrag plus Proviant (F2); Zugang später. |

## 5. Umsetzungsplan (TDD: jeweils Test zuerst)

Jeder Schritt einzeln mergebar. Aufwand: Klein / Mittel / Groß.

| Schritt | Inhalt | Agent | Aufwand |
|---|---|---|---|
| P0 | ✅ Owner-Entscheidungen 2026-10-23/24; GDD nachgezogen | Owner, game-designer | Klein |
| P1 | Datenmodell: Stufe pro Lauf je Figur und je Missionsfamilie , Stufen-Deckel aus Gebäudestufe (F5), Kosten je Stufe in Config. Keine Zähler; das Modell lässt Platz für einen späteren zweiten Fortschrittsbeitrag (Variante A, Quellen einzeln ausweisbar) | db-migration-agent, game-developer | Mittel |
| P2 | Investitions-Service: AP einzahlen bis Stufenschwelle (analog `ResearchService::invest()`), Deckel-Prüfung, Resourcebar-Sync | game-developer | Mittel |
| P3 | Hangar: Familien-Zuordnung in `config/missions.php`, Varianten-Ertrag je Stufe (F2, R2); `GameTick::processHangarMissions()` liest die Stufe | game-developer | Mittel |
| P4 | Cantina: Investition je Figur erst nach dem ersten Treffen im Lauf (O1, entschieden); Kodex-Auslöser bleiben unverändert (F3) | game-developer | Klein |
| P5 | UI: Balken mit AP-Chip (Kostenkonvention) an Figur und Familie, Investieren-Button | ui-specialist | Mittel |
| P6 | Texte, Glossar, `docs/game-reference.md`, Sol-Bericht-Zeile | content-writer | Klein |
| P7 | Cantina-Stufenwirkung je Figur (O4), eigener Design-Schritt | game-designer | Groß |

## 6. Messung und Bot

- **Bot-Abhängigkeit (offene Variable):** Der PlaytestBot kennt die Investition nicht. Ohne Bot-Regel „Familie/Figur trainieren“ misst ein Batch keinen Effekt; für die Senken-Hypothese braucht es eine Regel oder einen Hangar-First-Handtest. Bot-Verhalten ist die offene Variable.
- **Hypothese (Hebel: Investition, ein Hebel):** Hangar-First-Leerlauf-AP in Phase 1 sinkt von ca. 60 (Baseline 2026-10-20) spürbar, Phase-1-Ende bleibt unverändert. Messbar über `php tools/phase1-budget.php`.
- **Zu messen:** Credits-Bilanz nach Stufen-Ertrag (R2), wie oft Stufe 3 erreicht wird, Verteilung Breite gegen Tiefe.
- **Bot-Batch nur nach Rückfrage und Dauerschätzung.**

## 7. Nicht Teil dieser Spec

- Konkrete Kosten, Schwellen und Stufen-Erträge (Config, nach Messung).
- Cantina-Figur-Wirkungen je `game_role` (O4).
- Run-übergreifende Meta-Progression außer dem bestehenden Kodex.
- ROADMAP-Eintrag: gehört dem project-manager.
