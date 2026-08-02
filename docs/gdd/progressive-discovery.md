# Progressive Discovery System

> Ausgelagert aus [`docs/GDD.md`](../GDD.md) am 2026-08-02. Objective Discovery, Berater-Dialoge und Almanach.
>
> Kapitelnummerierung und `§`-Verweise beziehen sich weiterhin auf das GDD.

---

## 17. Progressive Discovery System

### Designprinzip

Nouron-Runs sind kurz und wiederholbar — aber kein Run soll sich identisch anfühlen. Das Progressive Discovery System ist das Bindeglied zwischen Roguelike-Variabilität (§15) und dem Spielerlebnis: **Der Spieler entdeckt das Spiel im Verlauf des Runs, nicht davor.**

Das Gegenprinzip wäre das klassische "Upfront-Reveal": Alle Objectives erscheinen sofort, alle Almanach-Artikel sind von Anfang an lesbar, alle Informationen liegen transparent auf dem Tisch. Das ist korrekt und spielerfreundlich — aber es beraubt den Run seiner Dynamik. Ein Run bei dem der Spieler von Sol 1 an den gesamten Weg kennt, fühlt sich wie eine Checkliste an, nicht wie eine Expedition.

**Das Ziel dieser Mechaniken ist deshalb nicht Informationsentzug, sondern zeitliche Dosierung**: Informationen kommen genau dann, wenn sie relevant und erfahrbar sind — nicht als Vorlesung, sondern als Teil des Spielgeschehens.

Die drei Mechaniken dieses Abschnitts sind eng verwandt:

- **§17.1 — Objective Discovery:** Phase-2-Objectives werden durch Berater-Dialoge über mehrere Sole enthüllt, nicht sofort beim Phasenübergang angezeigt.
- **§17.2 — Advisor Dialogs:** Berater führen strukturierte Multi-Sol-Dialoge. Sie können AP kosten und liefern dafür Informationen, Aufträge oder Bonuseffekte.
- **§17.3 — Almanach Unlock:** Bestimmte Almanach-Artikel sind anfangs gesperrt. Freischaltung durch Run-Fortschritt. Einmaliger Wissensbonus beim ersten Lesen.

Diese drei Mechaniken ziehen sich als **roter Faden** durch den gesamten Spielablauf. Sie sind unabhängig voneinander implementierbar, aber konzeptionell aufeinander aufgebaut: Berater-Dialoge (§17.2) liefern die narrative Schicht für Objective Discovery (§17.1), und Almanach-Einträge (§17.3) dokumentieren, was der Spieler durch Dialoge und Run-Fortschritt gelernt hat.

> Das System schreibt keine feste Spielerfahrung vor. Spieler, die Objectives ignorieren oder Almanach-Artikel nie öffnen, werden nicht bestraft. Discovery ist ein Angebot, keine Pflicht.

---

### 17.1 Objective Discovery — Berater enthüllen Phase-2-Aufgaben

#### Problem

Beim aktuellen Run-Design (§15) erscheinen alle drei Phase-2-Objectives sofort beim Übergang von Phase 1 zu Phase 2. Das ist klar und ehrlich — aber es tötet den Entdeckungsmoment. Der Spieler sieht sofort das vollständige Ziel-Set und plant rational. Die Transition von Phase 1 zu Phase 2 fühlt sich wie ein Menü-Wechsel an, nicht wie ein narrativer Wendepunkt.

#### Lösung: Gestaffelte Enthüllung

Phase-2-Objectives werden nicht sofort beim Phasenübergang angezeigt. Stattdessen enthüllen Berater die Objectives über die ersten **3–5 Sole** von Phase 2 durch individuelle Dialoge.

**Ablauf (Referenzbeispiel, 3 Objectives):**

| Sol nach Phase-2-Start | Ereignis |
|------------------------|---------|
| Sol +0 (Übergang) | INNN-Ereignis von Nexus: "Phase 1 abgeschlossen. Neue Direktive folgt." — keine Objectives sichtbar |
| Sol +1 | Berater-Dialog (z.B. Baumeister): Objective 1 wird enthüllt. AP-Kosten entstehen, wenn der Spieler den Dialog aktiv annimmt. |
| Sol +4–5 | Zweiter Berater (z.B. Analytiker) enthüllt Objective 2 |
| Sol +8–12 | Dritter Berater enthüllt Objective 3 oder Objective wird durch ein Run-Ereignis ausgelöst |
| Sol +15 | Sol-Threshold-Fallback: alle noch nicht enthüllten Objectives erscheinen automatisch |

Der Spieler kann bereits ab Sol +1 mit den Arbeiten beginnen — er muss das vollständige Objective-Set nicht kennen um sinnvoll zu handeln. Das erzeugt echte Spannung: "Was kommt als nächstes?"

#### Enthüllungs-Trigger

Objectives können durch drei verschiedene Trigger enthüllt werden:

| Trigger-Typ | Beschreibung | Beispiel |
|-------------|-------------|---------|
| `advisor_dialog` | Ein Berater-Dialog (§17.2) löst die Enthüllung aus | Baumeister berichtet nach erstem Sol von einem Nexus-Auftrag |
| `sol_threshold` | Fester Sol-Zeitpunkt nach Phase-2-Start | Objective erscheint spätestens ab Sol +15 (Fallback wenn kein Dialog ausgelöst wurde) |
| `run_event` | Ein bestimmtes Run-Ereignis löst das Objective aus | Korvette erkundet ein neues Tile → Objective "Expedition abschließen" wird sichtbar |

**Fallback-Regel:** Jedes Objective hat einen `reveal_by_sol`-Wert (Anzahl Sole nach Phase-2-Start). Wurde das Objective bis dahin nicht durch Dialog oder Event enthüllt, erscheint es automatisch — stilles INNN-Ereignis mit Absender "Nexus Command". Kein Objective bleibt für immer versteckt.

**UI-Darstellung:** Im Objectives-Screen gibt es einen dritten Zustand neben "in Bearbeitung" und "abgeschlossen": **"Unbekannt"** (Fragezeichen-Icon). Dieser Zustand zeigt, dass ein weiteres Objective existiert, aber noch nicht enthüllt wurde. So weiß der Spieler, dass er auf etwas wartet — ohne zu wissen was.

> ⚠️ BALANCE CONCERN: Der Fallback-Mechanismus ist wichtig. Wenn ein Spieler keinen Analytiker-Berater hat und das zweite Objective nur durch den Analytiker enthüllbar ist, muss der Sol-Threshold-Fallback greifen. Die Discovery-Mechanik darf keinen Progression-Lock erzeugen.

#### Roguelike-Variabilität

Pro Run sind nicht nur die Objectives selbst variabel (§15), sondern auch die Enthüllungs-Reihenfolge. Ein Run mit `task_research_lead` als Objective 1 kann dieses durch den Analytiker enthüllen — derselbe Run ohne Analytiker-Berater würde es durch Nexus (Sol-Threshold) enthüllen. Das erzeugt unterschiedliche narrative Erfahrungen ohne unterschiedlichen Spielinhalt zu erzwingen.

---

### 17.2 Advisor Dialogs — Multi-Sol-Dialoge

#### Konzept

Berater sind bisher passive AP-Produzenten mit Rang-Progression. Dialoge machen sie zu **aktiven Akteuren**: Sie sprechen den Direktoren gezielt an, bieten Informationen an, formulieren Bitten und können Aktionen anstoßen.

Ein Advisor Dialog ist ein strukturierter Informationsaustausch über **1–3 Sole**. Er erscheint als INNN-Ereignis mit Absender `[Berater-Typ]:[Berater-Name]`. Der Spieler kann den Dialog annehmen, verzögern oder ablehnen. Annehmen kann AP kosten.

#### Dialog-Struktur

Jeder Dialog hat folgende Felder:

| Feld | Beschreibung | Beispiel |
|------|-------------|---------|
| `dialog_key` | Eindeutiger Schlüssel | `engineer_phase2_objective_reveal` |
| `advisor_type` | Welcher Berater-Typ | `construction` |
| `trigger` | Was löst den Dialog aus | `phase2_start`, `run_event`, `sol_threshold` |
| `duration_ticks` | Wie viele Sole dauert der Dialog | 1–3 |
| `ap_cost_type` | AP-Typ der Entscheidungskosten | `construction`, oder `null` |
| `ap_cost_amount` | Wie viele AP kostet Annehmen | 3–8 |
| `reward_type` | Was liefert der Dialog | `objective_reveal`, `knowledge_hint`, `resource_bonus`, `none` |
| `is_skippable` | Kann der Spieler ablehnen | `true` / `false` |

**AP als Preis für Information:** Wenn ein Berater-Dialog AP kostet, sind das keine "Strafpunkte" — es ist der Preis für etwas Wertvolles. Ein Baumeister, der einen halben Sol damit verbringt, Nexus-Direktiven zu entziffern und dem Direktor zu erklären, steht in dieser Zeit nicht für Bauprojekte bereit. Das ist die saubere Nouron-Mechanik: **Opportunitätskosten statt Verbot** (§1.1).

> Designprinzip: Dialog-AP-Kosten sollten spürbar, aber nicht prohibitiv sein. Richtwert: 3–8 AP (entspricht 25–65% eines Junior-Berater-Tagespools). Ein Junior-Baumeister mit 10 AP/Sol kann sich den Dialog "leisten" — aber nicht an demselben Sol gleichzeitig ein Gebäude ausbauen. Das ist die Entscheidung.

#### Dialog-Verlauf

**Sol 1 — Ankündigung:**
INNN-Ereignis erscheint mit Absender-Name des Beraters. Kurze Eröffnung: "Direktor, ich habe etwas aufgegriffen das Ihre Aufmerksamkeit verdient. Haben Sie heute Zeit?"

Zwei Response-Optionen:
- "Jetzt anhören" — kostet sofort die definierten AP (oder 0 wenn `ap_cost_amount = null`)
- "Morgen" — Dialog verschiebt sich um 1 Sol (max. 2-mal verschiebbar, dann wird er automatisch aufgelöst)

**Sol 2 (oder selber Sol bei Sofortannahme) — Hauptdialog:**
Der eigentliche Inhalt erscheint: Objective-Enthüllung, Wissenshinweis, Ressourcen-Info oder narratives Lore-Fragment. Das INNN-Ereignis ist ausführlicher als ein Standard-Ereignis — max. 3 kurze Absätze.

**Sol 3 (optional) — Folgedialog oder Abschluss:**
Berater-Dialoge mit `duration_ticks = 3` haben einen Abschluss-Sol mit einer optionalen Reaktion des Spielers. Kein weiterer AP-Verbrauch.

#### Dialog-Typen (Phase 4 Katalog, Auswahl)

| Dialog-Key | Berater | Trigger | Kosten | Ergebnis |
|------------|---------|---------|--------|---------|
| `engineer_phase2_objective_reveal` | Baumeister | `phase2_start` + 1 Sol | 4 construction-AP | Objective 1 enthüllt |
| `scientist_anomaly_hint` | Analytiker | Anomalie-Tile erkundet | 5 research-AP | Event-Tile Tiefenscan-Kosten um 1 AP reduziert |
| `pilot_patrol_report` | Raumfahrer | 3+ Außenmissionen abgeschlossen | 0 AP | Verschleiß-Prognose des nächsten Dispatch detailliert aufgeschlüsselt (§7) |
| `trader_nexus_deal` | Konsul | Sol 20–30 (wenn `task_trade_volume` aktiv) | 6 economy-AP | Nexus-Handelsschiff erscheint 1 Sol früher als normal |
| ~~`strategist_threat_assessment`~~ | ~~Stratege~~ | — | — | **Entfallen 2026-08-02** — Stratege zurückgestellt (§13); der prognostizierte Ausgang wird stattdessen dauerhaft im Dashboard gezeigt (§13.4) |

> ⚠️ BALANCE CONCERN: Dialoge mit 0 AP-Kosten dürfen keinen spielentscheidenden Vorteil bieten. "Prognostizierter Gefahren-Ausgang" ist ein Komfort-Bonus, keine Entscheidungsverschiebung — das ist akzeptabel. Dialoge mit 5+ AP-Kosten müssen einen spürbaren Gegenwert liefern, sonst werden sie ignoriert.

#### Ablehnung und Verfall

Lehnt der Spieler einen Dialog explizit ab (`is_skippable = true`, Spieler wählt "Ablehnen"), wird er als `declined` abgeschlossen. Schiebt der Spieler ihn 2× auf ("Morgen") oder läuft `dialog_expire_after_ticks` ab, gilt er als `expired`. In beiden Fällen keine Konsequenz. Kein Dialog ist existenziell für den Run. Die Enthüllungen die über Dialoge transportiert werden, kommen im Zweifelsfall über den Sol-Threshold-Fallback (§17.1).

**Nicht verfügbare Berater:** Wenn der Berater-Typ des Dialogs gerade keinen aktiven Berater hat (Slot leer, Burnout, Außenmission), ist der Dialog nicht verfügbar. Er erscheint nicht im INNN-Feed. Der Sol-Threshold-Fallback greift stattdessen.

---

### 17.3 Almanach — Unlock & Wissensbonus

#### Konzept

Der Almanach (bisher als "Ingame-Nachschlagewerk" in der ROADMAP erwähnt) ist die In-Game-Dokumentation von Spielmechaniken, Gebäuden, Forschungen und Lore. Er ist ein wichtiges Onboarding-Werkzeug — aber er ist mehr als ein Handbuch: **Almanach-Artikel können einmalige Spielboni tragen**.

Das Lesen eines Artikels wird zu einer echten Spielentscheidung wenn es einen Bonus gibt. Statt "ich lese das irgendwann wenn ich Fragen habe" wird es zu: "Ich sollte jetzt den Geology-Artikel lesen bevor ich den Harvester verlagere — der Bonus hilft mir."

#### Freischaltsystem

Almanach-Artikel sind in drei Kategorien aufgeteilt:

| Kategorie | Freischalt-Bedingung | Beispiel-Artikel |
|-----------|---------------------|-----------------|
| **Immer verfügbar** | Kein Gate | Grundlegende Spielmechaniken (Supply, AP, Sol-Zyklus) |
| **Fortschrittsabhängig** | Sol-Zahl, Gebäude-Level oder Objective-Fortschritt | "Verfall & Entropie" freigeschaltet wenn erstes Gebäude < 80% SP |
| **Entdeckungsabhängig** | Explizites Ereignis (Berater-Dialog, Event-Tile) | "Stürme — Entstehung und Schutzmaßnahmen" freigeschaltet nach der ersten Kolonistengefahr (§9) |

**Freischalt-Trigger-Typen:**

| Trigger-Key | Bedingung |
|-------------|-----------|
| `sol_reached:{n}` | Run hat Sol n erreicht |
| `building_built:{key}` | Gebäude wurde erstmals gebaut |
| `objective_revealed:{key}` | Objective wurde enthüllt (§17.1) |
| `encounter_event:{type}` | Bestimmter Encounter-Typ ist aufgetreten |
| `advisor_dialog:{key}` | Berater-Dialog wurde abgeschlossen |
| `always` | Immer verfügbar |

**UI-Darstellung:** Gesperrte Artikel werden im Almanach-Index als grauer Eintrag mit dem Hinweis "Wird nach [Bedingung] freigeschaltet" gelistet. Der Spieler sieht, dass es dort etwas gibt — aber nicht den Inhalt. Das erzeugt Neugier ohne Frustration.

Neu freigeschaltete Artikel werden mit einem Badge "Neu" markiert und erscheinen in einer kompakten INNN-Meldung: "Almanach — neuer Artikel freigeschaltet: [Titel]".

#### Wissensbonus beim Lesen

Wenn ein Spieler einen Artikel mit einem Wissensbonus öffnet und vollständig liest (Scroll-Threshold oder expliziter "Gelesen"-Button), erhält er einmalig pro Run einen kleinen Vorteil. Der Bonus ist thematisch mit dem Artikel-Inhalt verknüpft.

**Bonus-Typen:**

| Bonus-Typ | Beschreibung | Beispiel |
|-----------|-------------|---------|
| `ap_bonus` | Sofortiger einmaliger AP-Schub eines Typs | +6 construction-AP nach Lesen von "Bautechnik — fortgeschrittene Methoden" |
| `resource_bonus` | Sofortiger einmaliger Ressourcen-Zuschuss | +30 Regolith nach Lesen von "Geologie — Abbauoptimierung" |
| `knowledge_hint` | Erhöht AP-Effizienz für eine bestimmte Kenntnis für N Sole | -1 AP pro research-Level für "Agronomie" für 5 Sole |
| `encounter_prep` | Mildert den Ausgang der nächsten Kolonistengefahr (§9) um eine Stufe | Nächster Sturm wird eine Ausgangsstufe milder bewertet (z.B. Kritisch → Beschädigt) |
| `none` | Kein Spielbonus — reines Lore oder Nachschlagewerk | Hintergrundgeschichte des Planeten |

**Einmalig pro Run:** Der Bonus wird nur beim ersten Lesen gutgeschrieben. Erneutes Öffnen des Artikels liefert keinen weiteren Bonus. Das Datum und der Erhalt des Bonus werden in `run_state` (oder einem neuen Feld `almanac_read_bonuses`) vermerkt.

**Lesbarkeits-Prinzip:** Artikel mit Bonus müssen kurz sein — max. 150–200 Wörter. Sie sind keine Romankapitel. Der Spieler soll nicht 10 Minuten lesen müssen um einen AP-Bonus zu bekommen. Das wäre kein Anreiz, sondern Arbeit.

> ⚠️ BALANCE CONCERN: AP-Boni über den Almanach dürfen die bestehende AP-Balance (§13) nicht aushebeln. Der Richtwert ist: ein Almanach-AP-Bonus entspricht dem Grundwert eines Tages (6 AP) oder dem Beitrag eines Junior-Beraters für 1 Sol (4 AP-Bonus). Höhere Boni sind nur für sehr späte oder sehr seltene Artikel akzeptabel. Die Boni summieren sich über einen Run: wenn alle freigeschalteten Artikel gelesen werden, sollte der Gesamteffekt spürbar, aber nicht spielverändernd sein.

> ⚠️ BALANCE CONCERN: Artikel mit `encounter_prep`-Bonus (Ausgangs-Milderung) müssen sicherstellen, dass Kolonistengefahren dadurch nicht trivial werden. Richtwert: maximal eine Ausgangsstufe Milderung, nur für die unmittelbar nächste Gefahr.

#### Almanach und Onboarding

Der Almanach ergänzt das Onboarding-System (§16) ohne es zu ersetzen. Das Onboarding zeigt dem Spieler was er jetzt tun soll; der Almanach erklärt warum Systeme so funktionieren wie sie funktionieren. Die Zielgruppen sind verschieden:

- **Neuer Spieler, erster Run:** Onboarding-Hints leiten, Almanach-Basics immer verfügbar
- **Spieler nach 3–5 Runs:** Onboarding deaktiviert, Almanach als Nachschlagewerk und Discovery-Tool

Almanach-Artikel die durch Berater-Dialoge (§17.2) freigeschaltet werden, erzeugen eine dritte Schicht: Der Dialog bringt den Spieler auf das Thema, der Artikel vertieft es. Das schafft einen kohärenten Informationsfluss ohne jeden Dialog zu einer Vorlesung zu machen.

---

### 17.4 Implementierungshinweise

Dieser Abschnitt beschreibt, was für Phase 4 konkret vorzubereiten ist. Es handelt sich um Design-Voraussetzungen, keine vollständige Implementierungsspezifikation.

#### Datenbank

**Neue Tabelle `advisor_dialogs`:**

```
advisor_dialogs
├── id                    ← PK
├── run_id                ← FK → runs
├── advisor_type          ← 'construction' | 'research' | 'navigation' | 'economy' | 'strategy'
├── dialog_key            ← Verweis auf Config-Definition, z.B. 'engineer_phase2_objective_reveal'
├── status                ← 'pending' | 'offered' | 'accepted' | 'declined' | 'expired'
├── offered_at_tick       ← Sol in dem der Dialog angeboten wurde
├── resolved_at_tick      ← Sol in dem der Dialog abgeschlossen oder verfallen ist
└── postponed_count       ← Anzahl "Morgen"-Antworten (max 2)
```

**Neue Spalten auf `run_objectives`:**

```
run_objectives
├── ...                   ← bestehende Felder (aus Phase 3i)
├── revealed_at_tick      ← nullable int: Sol in dem das Objective enthüllt wurde (null = noch nicht enthüllt)
└── reveal_trigger        ← nullable string: wie wurde es enthüllt ('advisor_dialog' | 'sol_threshold' | 'run_event')
```

**Neue Spalte auf `runs` oder erweitertes JSON-Feld:**

```
runs
├── ...                   ← bestehende Felder
└── almanac_read_bonuses  ← JSON: Liste von article_keys die gelesen + Bonus bereits gutgeschrieben wurden
```

**Neue Tabelle `almanac_articles`** (Stammdaten — wird per Migration/Seed befüllt, nicht pro Run):

```
almanac_articles
├── id                    ← PK
├── key                   ← eindeutiger Key, z.B. 'geology_extraction'
├── title                 ← Anzeigetitel (via lang/de)
├── category              ← 'mechanics' | 'buildings' | 'knowledge' | 'lore' | 'encounters'
├── unlock_trigger        ← JSON: Trigger-Definition, z.B. {"type": "building_built", "key": "harvester"}
├── bonus_type            ← nullable string: 'ap_bonus' | 'resource_bonus' | 'knowledge_hint' | 'encounter_prep' | null
├── bonus_value           ← nullable int/JSON: Wert des Bonus
└── bonus_ap_type         ← nullable string: AP-Typ wenn bonus_type = 'ap_bonus'
```

**Neue Tabelle `run_almanac_unlocks`** (welche Artikel sind in diesem Run freigeschaltet):

```
run_almanac_unlocks
├── id                    ← PK
├── run_id                ← FK → runs
├── article_key           ← FK → almanac_articles.key
└── unlocked_at_tick      ← Sol der Freischaltung
```

#### Config-Schlüssel

Neuer Block in `config/game.php → progressive_discovery`:

```php
'progressive_discovery' => [
    // Objective Discovery
    'objective_reveal_fallback_ticks' => 15,  // Sole nach Phase-2-Start bis Sol-Threshold-Fallback greift
    'objective_reveal_min_delay'      => 1,   // Minimale Sole zwischen zwei Objective-Enthüllungen

    // Advisor Dialogs
    'dialog_postpone_max'             => 2,   // Maximale Anzahl "Morgen"-Antworten
    'dialog_expire_after_ticks'       => 3,   // Dialog verfällt nach N Solen ohne Antwort

    // Almanach Bonus
    'almanac_bonus_ap_max_per_run'    => 30,  // Maximale kumulierte AP-Boni aus Almanach pro Run
    'almanac_bonus_resource_max_per_run' => 100, // Maximale kumulierte Ressourcen-Boni
],
```

Dialoge und Artikel-Definitionen kommen in eigene Config-Dateien:
- `config/advisor_dialogs.php` — alle Dialog-Definitionen (key, trigger, costs, reward)
- `config/almanac.php` — alle Artikel-Definitionen (key, category, unlock_trigger, bonus)

#### Tick-Integration

- **Advisor Dialogs:** Tick-Schritt 7 (Advisor Ticks) prüft für jeden aktiven Berater ob ein Dialog-Trigger erfüllt ist und legt ggf. eine neue `advisor_dialogs`-Zeile an. Bereits laufende Dialoge werden um `offered_at_tick + postponed_count` ausgewertet.
- **Objective Reveal:** Nach Tick-Schritt 7 prüft `RunProgressService` für jedes noch nicht enthüllte `run_objective` ob ein `reveal_trigger` ausgelöst wurde oder `sol_threshold` überschritten ist.
- **Almanach Unlock:** Nach Tick-Schritt 6 (Resource Generation) prüft ein neuer `AlmanachService::checkUnlocks()` für jeden definierten Trigger ob neue Artikel freigeschaltet werden.

#### Priorisierung für Phase 4

Die drei Teilmechaniken können unabhängig voneinander implementiert werden. Empfohlene Reihenfolge:

1. **Almanach-Grundstruktur** (Artikel-Tabelle, Freischalt-System, INNN-Benachrichtigung) — kleiner Aufwand, sichtbarer Effekt, keine Abhängigkeiten
2. **Objective Discovery via Sol-Threshold** (nur den Fallback implementieren, ohne Berater-Dialoge) — schafft sofort den Enthüllungseffekt mit minimalem Schema-Aufwand
3. **Advisor Dialogs** — aufwendiger, aber der narrativ reichhaltigste Teil. Setzt Almanach und Objective Discovery als Empfänger voraus.

Die vollständige Integration aller drei Teilmechaniken ist der Zielzustand. Jede Teilmechanik alleine bringt aber bereits Wert — es gibt keinen "alles oder nichts"-Implementierungspunkt.

---

