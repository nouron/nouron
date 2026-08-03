# Handoff — AP-Ratenmodell und Ressourcen-Zahlensatz

**Stand:** 2026-08-03 · **Vorarbeit:** PR #231 und #232, beide gemergt · **Status:** Design steht, **nichts ist implementiert**

Dieses Dokument ist die Übergabe an einen Agenten, der die Umsetzung beginnt. Es enthält den Kontext, der nicht aus dem Code hervorgeht — die Details stehen im GDD und in der ROADMAP, hier steht, was man wissen muss, um sie richtig zu lesen.

---

## 1. Worum es geht

Zwei Design-Runden (2026-08-01/02) haben das Aktionspunkt-System und die Ressourcen-Ökonomie neu aufgesetzt. Die Ergebnisse liegen vollständig im GDD. Was fehlt, ist die Implementierung — und zwei Freigaben des Owners.

**Die vier Richtungsentscheidungen:**

1. **Ein gemeinsamer AP-Pool** statt fünf getrennter, nicht mischbarer AP-Typen (§13.1)
2. **Ratenmodell** — AP fließen in sofortige Handlungen *und* in Projekte über mehrere Sole (§13.2)
3. **Stratege zurückgestellt** — vier Beratertypen statt fünf (§13)
4. **Harvester ohne Level-Up**, höchstens zwei Instanzen, dafür beweglich mit Erschöpfung (§4c, §13.5)

---

## 2. Die wichtigste Regel für die Arbeit an diesem Thema

**Die meisten Zahlenwerte in Config, Datenbank und GDD sind Platzhalter.** Sie sind entstanden, weil irgendein Wert dastehen musste, nicht weil sie hergeleitet wurden.

Das gilt für Baukosten, `decay_rate`, `supply_cost`, `ap_for_levelup`, Missionserträge, `bar.base_prices`, Verschleißraten und Kenntniskosten gleichermaßen.

> **Ein bestehender Wert ist kein Argument.** Wenn eine Rechnung nicht aufgeht, ist die erste Frage nicht „wie baue ich einen Ausgleich?", sondern „stimmen die zugrundeliegenden Werte überhaupt?".

Diese Regel steht im GDD unter **„Zum Umgang mit den Zahlen in diesem Dokument"** und ist dort mit der Liste der geschützten Werte hinterlegt. Sie wurde festgeschrieben, nachdem zwei Analyse-Runden genau diesen Fehler gemacht hatten: Der erste Regolith-Vorschlag eichte eine Kenntniskurve an `freighter.wear_per_sol` — einen Platzhalter — und leitete daraus einen „Sockel 8 → 12"-Workaround ab. Die zweite, unconstrained Runde kam auf einen Sockel von 20 mit halbierten Reparaturkosten und komplett anderen Baukosten.

**Geschützt sind nur diese sechs Werte** (Owner-Entscheidungen):

| Wert | Ort |
|---|---|
| Harvester ohne Level-Up | §13.5, §4c |
| CC `max_level = 5` | §4 |
| Run-Länge 100 Sole | §18.4 |
| Ein gemeinsamer AP-Pool | §13.1 |
| Vier Beratertypen | §13 |
| Werkstoffe bleiben als Ressource | §3 |

Dazu die **Knappheitsordnung** (§3), ebenfalls Owner-Entscheidung: `Regolith < Organika < Werkstoffe`. Regolith ist der Standard-Baustoff und soll verfügbar sein; Organika kann bei Missmanagement knapp werden; Werkstoffe bleiben am knappsten. Preise und Produktionsraten müssen das abbilden.

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

**§13.6 ist teilweise überholt.** Gültig bleiben AP-Grundwert, Berater-Beitrag, `f(L)`-Kostenkurve und Bonus-Kurve. Ersetzt sind alle Regolith-Zahlen und die Budgetprobe — die stehen in §13.7. Der Abschnitt bleibt bewusst stehen, weil der Vergleich zeigt, was der Methodenwechsel bewirkt hat.

---

## 4. Fünf Fallen, die Zeit kosten

**1. `game:sync-config` setzt den Harvester zurück.**
`config/buildings.php` hat `harvester.max_level = 8`, die laufende DB und `data/sql/testdata.sqlite.sql` haben **1**. `SyncConfig` schreibt die Config in die DB. Wer den Befehl ausführt, kippt die Owner-Entscheidung still. **Die Config ist anzugleichen, bevor irgendetwas anderes passiert.**

Nebenfolge: Die Glockenkurve aus PR #220 ist für den Harvester wirkungslos — bei `max_level = 1` greift nur `production_curve[27][3][1]`.

**2. `max_level` bedeutet zweierlei.**
Bei instanzierten Gebäuden ist es die maximale *Instanzzahl* (Config-Kommentar beim Wohnhabitat: „max 6 instances"), sonst das maximale *Level*. Deshalb kann kein Gebäude beides haben — und deshalb widerspricht der Techtree (Frachter = Hangar Lv2) der Config (Hangar ist instanziert). **Aufzuteilen in `max_instances` und `max_level`.**

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
Stufe 0   Owner-Freigaben (siehe Abschnitt 7) — blockiert Stufe 1
          + Config-Angleichung harvester.max_level

Stufe 1c  Messbarkeit herstellen                      ← HIER ANFANGEN
          - Instanz-Decay-Verdacht verifizieren
          - BotStrategy reparieren
          Beides klein, beides blockiert alles Weitere.

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

## 7. Was beim Owner liegt

Zwei Freigaben blockieren Stufe 1:

1. **AP-Struktur** (§13.6): Grundwert 10, Berater-Beitrag 2/3/4 statt 4/7/12, `f(1) = 0.5`, Boni additiv max. 42 %
2. **Regolith-Zahlensatz** (§13.7): Sockel 20 Rg/Sol, Reparatur 1 statt 2 Rg/SP, `decay_rate` in vier Klassen (0,5/0,8/1,0/1,5), Errichtung 70/95/120, Level-Up flach 25, CC-Ausbau × 30

Dazu offen, aber nicht blockierend: `bar.base_prices` nach der Knappheitsordnung (Vorschlag Rg 25 / Or 50 / Wk 110, `compound_import_price` 165).

---

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
