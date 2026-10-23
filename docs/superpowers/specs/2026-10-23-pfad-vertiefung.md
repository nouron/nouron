# Pfad-Vertiefung: Vertrautheit (Cantina) und Routine (Hangar) neben den Kenntnissen (Labor)

Stand 2026-10-23 · Autor: game-designer · Status: Entwurf, Owner-Fragen offen
GDD: §4b „Vertiefung: dieselbe Struktur in allen drei Pfadgebäuden“

## 1. Idee (Owner-Skizze)

Jedes Pfadgebäude hat mehrere Dinge, jedes Ding hat fünf Stufen. Labor: 7 Kenntnisse. Cantina: Beziehung zu Figuren. Hangar: Missionstypen. Die Skizze ist ein **Strukturmodell**, kein Bildschirm. Jede Oberfläche zeigt den Fortschritt in eigener Form (Techtree, Balken an der Figur, Balken am Missionstyp).

## 2. Entschiedene Punkte (Owner 2026-10-23)

- Aufstieg: Labor per AP-Investition; Cantina und Hangar per **Nutzung** (Gespräche/Anliegen bzw. Missionen). Beides kostet auch AP; Cantina und Hangar kosten zusätzlich andere Ressourcen. **Vorerst so akzeptiert**, später genauer designen.
- Stufen gelten pro Lauf. Kodex bleibt dauerhaft.
- Cantina-Figurenvielfalt bleibt vollständig (15 Figuren). Pro-Figur-Wirkungen sind ein **eigener Design-Schritt**, für Version 1 zweitrangig.
- Sichtbarkeit: Vertrautheitsbalken an der Figur; beim Missionstyp ähnlich.
- Gebäudestufe soll die Maximalstufe deckeln (Zuordnung offen, F5).

## 3. Bewertung

**Passt:** Die B6-Spec (V2 „Flugprogramm“) fragte schon nach einem Hangar-Gegenstück zur Labor-Forschung. Die Routine ist die allgemeinere Antwort. Der Barkeeper (4 Stufen aus wiederholtem Austausch) und der Kodex (n-te Begegnung schaltet Eintrag n frei) sind bereits Mini-Instanzen derselben Bauart.

**Risiken:**
1. **Keine Leerlauf-AP-Senke durch reine Nutzung.** Der Aufstieg kostet nur das, was die Mission oder das Gespräch ohnehin kostet. Löst B6/V2 (Hangar-First ca. 60 ungenutzte AP in Phase 1, gemessen 2026-10-20) deshalb **nicht** von selbst. Offene Variable, siehe F1.
2. **Überschneidung:** Raumfahrer-Rang und Kenntnis-Level wirken schon auf Erfolgschance und Proviant. Eine Routine mit Prozentbonus wäre ein dritter Stapel (Transparenz-Regel des Owners: Quellen einzeln zeigen).
3. **Cantina-Zufall:** Figuren erscheinen zufällig. Ohne Steuerbarkeit wäre Vertrautheit Glück. Siehe F4.
4. **Kodex-Doppelzählung:** Kodex zählt dauerhaft über alle Läufe, Vertrautheit pro Lauf. Zwei Zähler am selben Auslöser verwirren. Siehe F3.
5. **Gebäudestufen:** Cantina und Hangar haben höchstens Stufe 3 (§4c), die Vertiefung hat 5 Stufen.

## 4. Offene Fragen (mit Empfehlung; Empfehlungen sind Annahmen, keine Owner-Entscheidung)

| # | Frage | Empfehlung |
|---|---|---|
| F1 | Soll es für Hangar/Cantina zusätzlich eine **freiwillige AP-Investition** geben (Übung), damit die Leerlauf-AP aus B6 absorbiert werden? | Nein für Version 1, erst nach Handtest. Dann als V2 nachrüsten; die Routine-Struktur trägt es. |
| F2 | Was bringt eine Routine-Stufe im Hangar? Kandidaten: (a) Missionsvarianten freischalten, (b) Schwierigkeit „schwer“ erst ab Stufe X, (c) Verknüpfung mit Cantina-Missionsvarianten (Ideenpool). | (a), später (c). Nicht (b) (nimmt dem Hangar-First-Start Optionen), nicht Prozentbonus. Version 1 zeigt nur den Balken und die Schwellen ohne Wirkung. |
| F3 | Kodex und Vertrautheit: gleicher Auslöser, zwei Zähler? | Stufe n der Vertrautheit schaltet Kodex-Eintrag n frei (dauerhaft gespeichert). Ein Zähler, ein Balken. Ändert die Kodex-Regel „n-tes Zusammentreffen über Läufe“. |
| F4 | Wie steuert der Spieler die Vertrautheit, wenn Figuren zufällig erscheinen? | Version 1: zählt nur bei erscheinenden Figuren (kein Steuerhebel, ehrlich markiert). Danach eigener Design-Schritt (z. B. Figur „einladen“). Tomas (immer da) steuert schon heute. |
| F5 | Zuordnung Gebäudestufe zu Vertiefungsstufe? | Stufe 1–2 ab Gebäudestufe 1, 3–4 ab Stufe 2, 5 ab Stufe 3. Labor: Kenntnis-Level braucht weiterhin seine bestehenden Labor-Gates. |
| F6 | Missionstypen: Welche Bündelung? | Vier Familien: Erkundung (Botenflug, Erkundungsflug, Datensammelflug, Fernexpedition, Bergungsflug), Logistik (Versorgungs-, Handels-, Hilfsgütertransport), Bergung (Trümmerbergung, Ruinen-Expedition, Harvester-Bergung), Schutz (Konvoi, Patrouille). |
| F7 | Zählen Fehlschläge? | Ja, aber weniger als Erfolge („aus Fehlern lernt man“). Wert nach Messung. |

### 4a. Owner-Antworten (2026-10-23) und Rückfragen des Designers

| # | Entscheidung | Rückfrage / Wirkung |
|---|---|---|
| F1 | Nein, keine freiwillige AP-Investition in Version 1. | — |
| F2 | Missionsvarianten, je Stufe lukrativerer Ertrag; **die neue Variante ersetzt die alte** (Owner, zweite Runde). | Entschieden. Ersetzen heißt: Fortschritt wie bei Kenntnissen, keine Wahl zwischen Varianten; die Entscheidung liegt in Breite gegen Tiefe über die Familien. Folgen siehe R2. |
| F3 | Entschieden: getrennt lassen (siehe R3). | Ein Treffen erhöht Vertrautheit und Kodex-Zähler getrennt. |
| F4 | Version 1 ohne Steuerhebel. Zuverlässige Gäste: Barkeeper und Reisender Händler (Corvan). Rest später. | Version 1 zählt Vertrautheit bei jeder Begegnung. Corvan kommt im festen Intervall und ist damit steuerbar planbar, Tomas jederzeit. Zufallsfiguren bleiben der offene Punkt. |
| F5 | Entschieden: Version 1 nur Gebäudestufe. Berater-Rang-Gate später, nur für Stufe 5, als Weiterkommens-Gate, für alle drei Pfade gleichzeitig. **Berater-Außendienst und Berater-Burnout sind als Features zurückgestellt und komplett aus dem GDD genommen** (Owner 2026-10-23; beide standen nur im GDD, nie im Code). | Siehe R5. Der Einwand 3 („wackelnder Zustand“) entfällt damit. Originaltext: `docs/gdd/archiv-berater-aussendienst-burnout.md`. |
| F6 | Entschieden: 5 Familien wie unten. | Eine sechste Familie nur bei Bedarf. |

**R2 — Folgen von „ersetzt“.** (1) Der Ertrag steigt mit der Stufe für jede Mission der Familie. Das ist ein Wert-Anstieg wie ein Prozentbonus, nur sichtbar als benannte Variante („Erkundungsflug II“). Damit die Transparenz-Regel hält, zeigt der Missionsdialog die Variante mit ihren echten Zahlen. (2) **Credits-Inflation:** Missions-Credits sind gerade verdoppelt (Owner 2026-09-30). Die Stufen-Steigerung muss klein bleiben und ist vor Freigabe zu messen. (3) Der Preis je Variante (Strecke, Proviant, Verschleiß) ist **optional**: Wächst nur der Ertrag, ist die Vertiefung reiner Fortschritt. Ich empfehle, den Proviant leicht mitwachsen zu lassen, sonst wird der Organika-Hebel entwertet. (4) Die Missionswahl „leicht/normal/schwer“ bleibt innerhalb jeder Variante bestehen. (5) Aufwand sinkt: keine ~20 parallelen Missionen, sondern eine Stufenformel je Mission (Basiswert × Stufe) plus Namen/Texte. Kein Regolith-Ertrag (T31).
| F7 | Erfolge zählen voll, Fehlschläge weniger. | Wert nach Messung. |

**R3 — Kodex gegen Vertrautheit, mit Kontext.** Der Kodex sind die Hintergrund-Texte je Figur. Er ist dauerhaft: Er zählt Begegnungen über alle Läufe hinweg. Das n-te Treffen insgesamt schaltet Eintrag n frei. Die Vertrautheit dagegen gilt pro Lauf und beginnt bei jedem Lauf von vorn. Beide hängen am selben Ereignis (Treffen mit der Figur). Meine erste Empfehlung war, Stufe n der Vertrautheit solle Eintrag n freischalten, also nur noch ein Zähler. Das hat ein Problem: Eine selten erscheinende Figur trifft man pro Lauf vielleicht zweimal. Dann käme der Kodex nie über Eintrag 2 hinaus, obwohl er heute über viele Läufe vollständig wird. **Neue Empfehlung:** getrennt lassen. Ein Treffen erhöht beide Zähler. Der Balken an der Figur zeigt den Lauf. Der Kodex bleibt die Sammlung über alle Läufe. Der Preis: zwei Zähler am selben Ereignis. Das ist erträglich, weil sie an verschiedenen Orten stehen (Balken an der Figur, Kodex im Kodex-Bereich) und das UI den Unterschied benennt („in diesem Lauf“ gegen „insgesamt“).

**R5 — Berater-Rang als Gate für die Höchststufe.** Ränge: 1 bis 3, Beförderung kostet Credits und ist eine aktive Entscheidung (GDD §13). Dafür spricht: Der Berater gewinnt Sinn jenseits seiner AP, und die Beförderungs-Credits sind ein sinnvoller Sink. Dagegen spricht:
1. **Dritter Gate-Typ.** Aktuell gelten Gebäudestufe (und CC-Stufe). Das GDD nennt eine dritte Bedingungsart schon bei den Missionen „schwer kommunizierbar“ (§8b).
2. **Konsul-Regel.** §12 legt fest, dass Cantina-Hebel nie vom Konsul-Rang abhängen sollen. Ein Rang-Gate auf die Vertrautheits-Höchststufe wäre genau diese Kopplung, auch wenn es kein Credits-Hebel ist.
3. ~~**Wackelnder Zustand.**~~ Entfallen: Außendienst und Burnout sind zurückgestellt. Offen bleibt nur die Nexus-Sanktion (sperrt einen zufälligen Berater für einige Sole); ein Fortschritts-Gate (erreichte Stufen bleiben) verträgt das.
4. **Parität.** Dann müsste das Labor ein Analytiker-Rang-Gate bekommen.
**Empfehlung:** Version 1 nur mit Gebäudestufe (wie beschlossen). Falls der Berater-Rang später dazukommt, dann nur als Fortschritts-Gate für Stufe 5 („Aufsteigen geht nur mit Rang 3“), nicht als Effekt-Gate (erreichte Stufen bleiben immer erhalten), und gleichzeitig für alle drei Pfade. Wenn du das willst, brauche ich eine Entscheidung zum Konsul-Punkt.

**F6 — neuer Zuschnitt: 5 Missionsfamilien** (Annahme; der Zuschnitt folgt den Grundbedürfnissen aus §4b, damit jede Familie ein klares Profil hat):

| Familie | Missionen | Profil |
|---|---|---|
| Erkundung | Erkundungsflug, Datensammelflug, Fernexpedition | Information, Kenntnis-AP |
| Bergung | Bergungsflug, Trümmerbergung, Ruinen-Expedition, Harvester-Bergung | Funde, Werkstoffe (Ruinen-Missionen können durch T31 wegfallen; die Familie bleibt dann mit zwei) |
| Versorgung | Versorgungsfahrt, Hilfsgütertransport | Organika, Vertrauen |
| Handel und Kurier | Botenflug, Handelsfahrt | Credits |
| Schutz | Konvoi-Begleitung, Umkreis-Patrouille | Korvetten-Einsatz |

Eine Familie kann Schiffe mischen (Botenflug Drohne, Handelsfahrt Frachter). Das ist gewollt: Die Routine gehört der Kolonie, nicht dem Schiff.

## 5. Umsetzungsplan (TDD: jeweils Test zuerst)

Reihenfolge nach Nutzen und Risiko. Jeder Schritt ist einzeln mergebar. Aufwand: Klein / Mittel / Groß.

| Schritt | Inhalt | Agent | Aufwand |
|---|---|---|---|
| P0 | ✅ 2026-10-23: Owner hat F1–F7 beantwortet; GDD nachgezogen | Owner, game-designer | Klein |
| P1 | Datenmodell: Zähler pro Lauf für Figur und Missionstyp (eine Tabelle oder zwei, je nach F3); Schwellen in Config | db-migration-agent, game-developer | Mittel |
| P2 | Hangar: Missionstyp-Zuordnung in `config/missions.php`, Routine zählt beim Abschluss (`GameTick::processHangarMissions()`) | game-developer | Klein |
| P3 | Cantina: Vertrautheit zählt an den bestehenden Kodex-Auslösern (`BarService`, Anliegen, Deva/Lenn, Barkeeper) | game-developer | Mittel |
| P4 | UI: Vertrautheitsbalken an der Figur, Routine-Balken am Missionstyp, jeweils mit nächster Schwelle (AP-Chip-Konvention beachten, Resourcebar-Sync) | ui-specialist | Mittel |
| P5 | Texte, Glossar, `docs/game-reference.md` (Schwellen), Sol-Bericht-Zeile | content-writer | Klein |
| P6 | Version 2: Stufen-Wirkung Hangar (F2), Figur-Wirkungen Cantina (eigener Design-Schritt), ggf. freiwillige Übung (F1) | game-designer, dann Umsetzung | Groß |

## 6. Messung und Bot

- **Bot-Abhängigkeit (offene Variable):** Der PlaytestBot wählt Missionen und Cantina-Begegnungen nach Utility-Gewichten. Ohne Regel „Routine/Vertrautheit pflegen“ misst ein Batch nur Zufallsfortschritt. Für Version 1 reicht reine Beobachtung (Stufe pro Typ am Lauf-Ende im RunReport), keine Bot-Regel.
- **Kein Balance-Hebel in Version 1:** Da Stufen noch nichts ändern, bleibt Phase-1-Ende und Credits-Bilanz unverändert. Kontrollmessung nur zur Sicherheit; **Bot-Batch nur nach Rückfrage und Dauerschätzung.**
- **Zu messen, sobald Wirkungen existieren (P6):** wie oft Typ-Stufe 3 erreicht wird, Verteilung Breite gegen Tiefe, Leerlauf-AP Hangar-First gegen Baseline 2026-10-20.

## 7. Nicht Teil dieser Spec

- Konkrete Schwellenwerte und Stufen-Erträge (Config, nach Messung).
- Cantina-Figur-Wirkungen je `game_role` (eigener Design-Schritt).
- Run-übergreifende Meta-Progression außer dem bestehenden Kodex.
- ROADMAP-Eintrag: gehört dem project-manager (T-Punkt mit Verweis auf diese Spec).
