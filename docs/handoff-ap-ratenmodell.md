# Handoff — AP-Ratenmodell und Ressourcen-Zahlensatz

**Stand:** 2026-08-03 · **Vorarbeit:** PR #231, #232, #233 · **Status:** Design steht und ist **freigegeben**, nichts ist implementiert

Dieses Dokument ist die Übergabe an einen Agenten, der die Umsetzung beginnt. Es enthält den Kontext, der nicht aus dem Code hervorgeht — die Details stehen im GDD und in der ROADMAP, hier steht, was man wissen muss, um sie richtig zu lesen.

---

## 1. Worum es geht

Drei Design-Runden (2026-08-01 bis -03) haben das Aktionspunkt-System und die Ressourcen-Ökonomie neu aufgesetzt. Die Ergebnisse liegen vollständig im GDD und sind vom Owner **freigegeben**. Was fehlt, ist die Implementierung.

**Die vier Richtungsentscheidungen:**

1. **Ein gemeinsamer AP-Pool** statt fünf getrennter, nicht mischbarer AP-Typen (§13.1)
2. **Ratenmodell** — AP fließen in sofortige Handlungen *und* in Projekte über mehrere Sole (§13.2)
3. **Stratege zurückgestellt** — vier Beratertypen statt fünf (§13)
4. **Harvester ohne Level-Up**, höchstens zwei Instanzen, dafür beweglich mit Erschöpfung (§4c, §13.5)

**Die freigegebenen Zahlen** (2026-08-03) stehen in §13.6 (AP) und §13.7 (Regolith), jeweils im ✅-Kasten am Anfang des Abschnitts. Beide Kästen enthalten außerdem eine Tabelle „wenn im Playtest X passiert, ist Y die Stellschraube — **nicht** Z". **Die ist beim Nachjustieren wichtiger als der Ausgangswert** — mehrere der Zahlen sind gegeneinander verspannt, und der naheliegende Regler ist oft der falsche.

---

## 2. Die wichtigste Regel für die Arbeit an diesem Thema

**Die meisten Zahlenwerte in Config, Datenbank und GDD sind Platzhalter.** Sie sind entstanden, weil irgendein Wert dastehen musste, nicht weil sie hergeleitet wurden.

Das gilt für Baukosten, `decay_rate`, `supply_cost`, `ap_for_levelup`, Missionserträge, `bar.base_prices`, Verschleißraten und Kenntniskosten gleichermaßen.

> **Ein bestehender Wert ist kein Argument.** Wenn eine Rechnung nicht aufgeht, ist die erste Frage nicht „wie baue ich einen Ausgleich?", sondern „stimmen die zugrundeliegenden Werte überhaupt?".

Diese Regel steht im GDD unter **„Zum Umgang mit den Zahlen in diesem Dokument"** und ist dort mit der Liste der geschützten Werte hinterlegt. Sie wurde festgeschrieben, nachdem zwei Analyse-Runden genau diesen Fehler gemacht hatten: Der erste Regolith-Vorschlag eichte eine Kenntniskurve an `freighter.wear_per_sol` — einen Platzhalter — und leitete daraus einen „Sockel 8 → 12"-Workaround ab. Die zweite, unconstrained Runde kam auf einen Sockel von 20 mit halbierten Reparaturkosten und komplett anderen Baukosten.

**Geschützt sind nur diese Werte** (Owner-Entscheidungen, vollständige Liste im GDD unter „Zum Umgang mit den Zahlen"):

| Wert | Ort |
|---|---|
| Harvester ohne Level-Up | §13.5, §4c |
| CC `max_level = 5` | §4 |
| Run-Länge 100 Sole | §18.4 |
| Ein gemeinsamer AP-Pool | §13.1 |
| Vier Beratertypen | §13 |
| Werkstoffe bleiben als Ressource | §3 |
| Knappheitsordnung `Regolith < Organika < Werkstoffe` | §3 |
| AP-Struktur inkl. `ap.base = 12` | §13.6 |
| Regolith-Zahlensatz inkl. `decay_rate` 0,40/0,60/0,80/1,20 | §13.7 |
| `max_instances` als eigenes Feld | §4c |

Zur **Knappheitsordnung**: Regolith ist der Standard-Baustoff und soll verfügbar sein; Organika kann bei Missmanagement knapp werden; Werkstoffe bleiben am knappsten. Preise und Produktionsraten müssen das abbilden.

**Wichtig:** Die vier zuletzt genannten sind seit dem 2026-08-03 beschlossen, aber sie sind *Setzungen mit Vertrauensgrad*, keine Naturgesetze — siehe Abschnitt 7. Geschützt heißt hier: nicht beiläufig beim Balancing verändern, sondern nur mit Rückfrage beim Owner und nur entlang der jeweils dokumentierten Stellschraube.

---

## 3. Wo was steht

| Thema | Ort |
|---|---|
| Knappheitsordnung der Ressourcen | GDD §3 |
| Die drei Pfade und die Paritäts-Anforderung | GDD §4b |
| Instanzen oder Level je Gebäude | GDD §4c |
| Die drei Begrenzungsachsen (Breite/Tiefe/Tempo) | GDD §6 |
| AP-Pool, Ratenmodell, Boni, Dashboard | GDD §13.1–13.4 |
| Instandhaltungslast, Harvester, Regolith-Beschaffung | GDD §13.5 |
| AP-Zahlenvorschlag (gültig) | GDD §13.6 |
| Regolith-Zahlensatz, hergeleitet (gültig) | GDD §13.7 |
| Offene Punkte, nach Dringlichkeit sortiert | GDD Anhang A |
| Drifts zwischen GDD, Config und Code | GDD Anhang B |
| Umsetzungsplan in Stufen | ROADMAP „Phase 3o" |

**§13.6 ist teilweise überholt — auf den ✅-Kasten am Anfang achten.** Gültig bleiben Berater-Beitrag, `f(L)`-Kostenkurve und Bonus-Kurve. **Geändert:** der AP-Grundwert (10 → 12). **Ersetzt:** alle Regolith-Zahlen und die Budgetprobe — die stehen in §13.7. Der Abschnitt bleibt bewusst stehen, weil der Vergleich mit §13.7 zeigt, was der Methodenwechsel bewirkt hat.

---

## 4. Fünf Fallen, die Zeit kosten

**1. `game:sync-config` setzt den Harvester zurück.**
`config/buildings.php` hat `harvester.max_level = 8`, die laufende DB und `data/sql/testdata.sqlite.sql` haben **1**. `SyncConfig` schreibt die Config in die DB. Wer den Befehl ausführt, kippt die Owner-Entscheidung still. **Die Config ist anzugleichen, bevor irgendetwas anderes passiert.**

Nebenfolge: Die Glockenkurve aus PR #220 ist für den Harvester wirkungslos — bei `max_level = 1` greift nur `production_curve[27][3][1]`.

**2. `max_level` bedeutet zweierlei — und das ist der erste Blocker.**
Bei instanzierten Gebäuden ist es die maximale *Instanzzahl* (Config-Kommentar beim Wohnhabitat: „max 6 instances"), sonst das maximale *Level*. Für den **Harvester kollidieren dadurch zwei beschlossene Aussagen in einem Feld**: „kein Level-Up" (§13.5) und „Deckel 2 Instanzen" (§4c). Der Hangar braucht beide Achsen gleichzeitig (Instanzen = Schiffsplätze, Level = Schiffsklasse), was der Techtree ohnehin voraussetzt.

**`max_instances` als eigenes Feld ist beschlossen** (Owner, 2026-08-03). Beide nullable, `NULL` = unbegrenzt. Schema-Arbeit für `db-migration-agent`, **muss vor der Umsetzung von §13.7 und §4c stehen.**

**3. Verdacht auf superlinearen Instanz-Decay.**
`GameTick::processBuildingDecay()` schreibt mit `['colony_id', 'building_id']` ohne Instanz-Unterscheidung. Wenn instanzierte Gebäude dadurch mehrfach verfallen, bestraft sich jede Umstellung auf Instanzen sofort selbst — und §4c stellt zwei Gebäude um. **Vor der Umstellung verifizieren, nicht danach.** Regressionstest: zwei Hangar-Instanzen, ein Tick, SP beider Zeilen prüfen.

**4. Der Playtest-Bot kann keinen Frachter kaufen.**
`tests/Feature/Playtest/BotStrategy.php` kauft hartkodiert eine Drohne (`ship_id => 85`), deckelt auf genau ein Schiff (`! hasAnyShip`) und heuert den Raumfahrer nie an (`HIRE_ORDER = [35, 36, 92]`). Der ROADMAP-Befund „Spieler kommt nie zum Frachter" ist deshalb ein **Messartefakt**, kein Spielbefund. Solange das so ist, ist jede Pfad-B-Messung wertlos.

**5. Die Cantina-Diagnose stand falsch herum.**
Die Credits→Ressource-**Kauf**richtung existiert und ist mit 60 % der Regelfall; die **Verkaufs**richtung existiert überhaupt nicht (der Code-Kommentar in `BarService.php` Z. 305 sagt das Gegenteil des Codes darunter). Das „Not enough resources." kommt von den Losgrößen: `rand(1,5) × 10` Einheiten ergibt ~1.400 Cr Erwartungswert je Angebot gegen +5 Cr/Sol Netto-Einkommen.

---

## 5. Was schon existiert und nicht neu gebaut werden muss

- **Projekt-Investition über mehrere Sole.** `ap_spend` liegt bereits auf `colony_buildings`, `colony_research` und `colony_ships`. Das Ratenmodell muss sie nur vom AP-Typ entkoppeln und um Kostenkurve und Bonus-System ergänzen.
- **Erschöpfungs-Grundlagen.** `colony_tiles.resource_max` ist im Schema als „Basis für Erschöpfungs-Counter" beschrieben; die drei Ergiebigkeitsstufen `regolith_rich/normal/poor` existieren, ebenso die Verlege-Vorschau mit Ertragsvergleich.
- **Playtest-Bot** als Messumgebung (Phase 3n), inklusive `RunReport`-JSON-Artefakten.

---

## 6. Der Plan

Vollständig in der ROADMAP unter **„Phase 3o"**. Kurzfassung mit den Abhängigkeiten:

```
Stufe 0   ✅ erledigt — alle Freigaben erteilt (2026-08-03)

Stufe 1a  Schema + Messbarkeit                        ← HIER ANFANGEN
          - max_instances als eigenes Feld (db-migration-agent)
          - Instanz-Decay-Verdacht verifizieren
          - BotStrategy reparieren
          - config/buildings.php: harvester.max_level angleichen
          Alles klein, alles blockiert Stufe 1.

Stufe 1   Zahlensatz in EINEM Zug (ein PR)
          Sockel ohne neue Baukosten = triviale Wirtschaft.
          Neue Baukosten ohne Sockel = unspielbar.
          Enthält: kompletter §13.7-Satz, harvester.max_level,
          Instanz-Preisregel, max_level-Aufteilung, Wachstumsachsen
          aus §4c, geology-Hook, bar.base_prices, knowledge-Kosten

Stufe 1b  Klein, danach: mission_supply_run sol_distance,
          mission_aid_transport ungegatet, Cantina-Losgrößen,
          Harvester-Erschöpfung, Agrardom-Kurve

Stufe 1d  Nächste Design-Runde: die Supply-Achse (kein Code)

Stufe 2   AP-Pool zusammenlegen
Stufe 3   Ratenmodell vervollständigen (f(L), Boni, Restzeit)
Stufe 4   Kommandozentrale-Dashboard
Stufe 5   Instrumentierung, Playtest, Kalibrierung
Stufe 6   Nachzieharbeiten (Onboarding-Texte, Drifts, ResetPlayer)
```

**Einstiegspunkt: Stufe 1c.** Nicht Stufe 1 — ohne die beiden Punkte wird gegen einen möglichen Bug balanciert und ohne Messgrundlage kalibriert.

---

## 7. Freigegebene Zahlen und ihr Vertrauensgrad

Alles freigegeben am 2026-08-03. Nichts liegt mehr beim Owner.

**Tragende Zahlen — sieben.** Falsch gesetzt bricht etwas, und der Playtest zeigt es nicht unbedingt von allein:

| Zahl | Wert | Vertrauen |
|---|---|---|
| `ap.base` | **12** | mittel-hoch |
| `f(1)` | **0,5** | hoch — folgt aus der Währungstrennung |
| `advisor.ap_per_rank` | **[2, 3, 4]** | mittel |
| Harvester-Frischwert (`regolith_normal`) | **18** | mittel |
| Reparatur je SP | **1 Regolith** | hoch — Strukturargument |
| `decay_rate`-Klassen | **0,40 / 0,60 / 0,80 / 1,20** | mittel |
| Errichtung / Level-Up | **70 / 95 / 120** gegen **flach 25** | mittel-hoch |

**Feintuning — alles Übrige.** `base_ap`-Klassen, Steigung von `f(L)`, `project_min_cost_factor`, Handlungs-AP, CC-Ausbau ×30, `bar.base_prices`, `compound_import_price`, `mission_supply_run`, `geology`-Kurve, Kenntniskosten, Startbestand. Aus dem Bot-Report korrigierbar, keine davon bricht etwas.

Eine Ausnahme mit Struktur-Charakter: Die **Preisrelation** aus der Knappheitsordnung (§3) ist tragend, auch wenn die konkreten Werte Feintuning sind.

**Der unsicherste Punkt, als Erstes zu messen:** die Harvester-Erschöpfung (§4c). Vertrauensgrad niedrig-mittel — die Standzeiten sind sauber gerechnet, aber die Umzugsfrequenz hängt daran, wie gut der Spieler die Karte kennt, und das hängt an der Erkundung, die ihrerseits AP kostet. Messgrößen: Umzüge pro Run (Ziel 4–6) und Anteil der Sole, in denen ein Harvester unter 60 % Ertrag fördert (Ziel < 30 %).

## 8. Arbeitsweise

- **TDD ist verbindlich** (CLAUDE.md): erst ein fehlschlagender Test, der das gewünschte Verhalten beschreibt, dann die minimale Implementierung. Gilt für Services, Controller-Logik, Spielmechaniken und Migrations mit Datenlogik.
- **Nie direkt auf `master`.** Branch anlegen, committen, pushen, PR erstellen.
- **CHANGELOG-Eintrag** am Ende jeder Session mit Code-Arbeit. Der Pre-Merge-Hook blockiert sonst.
- **Subagenten** (`.claude/agents/`) proaktiv einsetzen — `game-designer` vor jeder neuen Mechanik, `qa-tester` für die Tests, `db-migration-agent` für Schema. **Wer eine Balance-Aufgabe delegiert, muss die Platzhalter-Regel aus Abschnitt 2 explizit mitgeben** — sonst entstehen Vorschläge, die Bestandswerte als Randbedingung behandeln.

---

## 9. Offene Design-Fragen (kein Implementierungsauftrag)

Vollständig in GDD Anhang A.4. Die wichtigsten:

- **Die Supply-Achse neu herleiten** — die `supply_cost`-Werte sind gegen eine Wirtschaft kalibriert, in der Regolith knapper war. Dazu gehören drei Deckel-Fragen, die gemeinsam bestimmen, wie tief eine Kolonie wachsen kann: Level-Deckel für Cantina und Krankenstation (beide heute unbegrenzt), Instanz-Deckel für den Agrardom, und die übrigen `max_level = NULL`-Gebäude.
- **Pfad-C-Regolith-Hebel** — der Organika→Regolith-Tausch fällt mit der Knappheitsordnung weg. Offen, ob Pfad C überhaupt einen großen Regolith-Hebel braucht.
- **`geology`-Produktionsbonus** — möglicherweise überflüssig, wenn Regolith-Wachstum über Harvester-Instanzen läuft.
- **Harvester-Erschöpfungsrate** — die Grundproduktion in §13.7 unterstellt einen frischen Standort; mit Erschöpfung ist das ein Start-, kein Dauerwert.
- **Stratege** — später neu bewerten und designen.
