# ADR 0003: Simultanes Turn-Resolution-System — Multiplayer-Architektur
Datum: 2026-07-01
Status: Akzeptiert (Umsetzung zurückgestellt — Vorentscheidungen sofort wirksam)

## Kontext

Nouron ist aktuell Singleplayer (Roguelike Mini-4X, 1 Spieler pro Run). Ein optionaler
Multiplayer-Modus für 1–4 Spieler ist für eine spätere Phase geplant. Mehrere Architektur-
entscheidungen müssen jetzt getroffen werden, damit die Singleplayer-Codebasis den
Multiplayer nicht nachträglich blockiert.

### Kernspannung: Interaktives Aktionsmodell vs. Batch-Order-Modell

Das aktuelle Spiel funktioniert interaktiv: jede Spieleraktion ist ein eigener HTTP-Request
(`ColonyController::placeBuilding()`, `PersonellService::hire()`, etc.) mit sofortiger DB-
Änderung. Multiplayer erfordert ein **Batch-Order-Modell**: Alle Spieler planen ihren Zug
parallel, Aktionen werden gesammelt und erst bei gemeinsamer Auflösung angewendet.

Diese Spannung ist der größte Migrations-Aufwand für Multiplayer und muss bewusst gemanagt
werden:

- **Singleplayer bleibt interaktiv** (kein Umbau jetzt).
- Bei Multiplayer-Einführung: Singleplayer-Modus = 1 Spieler, „alle ready" nach jeder
  Aktion → sofortige Resolution. Der interaktive Eindruck bleibt erhalten; intern läuft
  dieselbe Pipeline.

### `games` ersetzt `runs` langfristig

Das bestehende `runs`-Konzept (Run-Modell, Roguelike-Framing) und das geplante `games`-
Konzept werden zu einem einzigen Modell zusammengeführt. Ein Singleplayer-Run ist ein
`game` mit `player_count = 1`. Die Roguelike-Terminologie (Run, Sol) bleibt player-facing;
intern wird `games` die kanonische Entität.

Migration erfolgt bei Multiplayer-Einführung, nicht jetzt. Bis dahin bleibt `runs` bestehen.

---

## Entscheidungen

### 1. Simultane Zugplanung mit Hybrid-Trigger

Alle Spieler planen ihren Zug parallel (keine Zugreihenfolge). Auflösung erfolgt:
- **Sofort**, sobald alle aktiven Spieler `is_ready = true` gesetzt haben, ODER
- **Spätestens** bei Ablauf eines konfigurierten Zeitlimits (`turn_time_limit_seconds`,
  Bereich: 300s–86400s, konfigurierbar pro Spiel in `config/game.php`).

Bei Deadline-Ablauf: letzter gespeicherter (auch unvollständiger) Autosave-Stand wird
angewendet. Kein Alles-oder-Nichts, kein reines Passen. Fehlt jeder Eintrag: `emptyOrder()`.

Idempotenz-Schutz im `ResolveTurnJob`: DB-Transaktion mit `lockForUpdate()` auf `games`,
Check ob `current_turn + phase` noch passen — schützt gegen Race zwischen Sofort- und
Deadline-Trigger.

**SQLite-Einschränkung:** `lockForUpdate()` unter SQLite fällt auf Table-Level-Lock zurück
(kein Row-Level-Locking). Bei 1–4 Spielern akzeptabel. Bei PostgreSQL-Migration entfällt
diese Einschränkung automatisch.

### 2. Konfliktauflösung bei exklusiven Zielen — Option B

Bei gleichzeitigem Zugriff von ≥2 Spielern auf dasselbe exklusive Ziel (freies Feld,
begrenzt verfügbare Ressource, einmalige Aktion an einem Ort) im selben Zug gilt:

**Option B: Alle konkurrierenden Aktionen scheitern. Das Ziel bleibt unverändert.**
Alle beteiligten Spieler können es in der Folgerunde erneut versuchen. Eingesetzte
Ressourcen/Einheiten bleiben unverändert erhalten (keine Aktion wird „verbraucht").

Dies ist ein generisches Prinzip der `TurnResolutionEngine` über einen `ExclusiveClaimResolver`,
nicht per Aktionstyp hartkodiert. Welche Aktionstypen als „exklusiv" gelten, wird über eine
erweiterbare Markierung am jeweiligen Action-Handler konfiguriert (nicht in der Engine).

Konkrete Anwendungsfälle werden ergänzt, sobald das Mechanik-Set für Multiplayer feststeht.

### 3. Verpasste Züge — kein Elimination-Mechanismus

`missed_turns_streak` ist ein rein informativer Zähler (hochzählen wenn kein/leerer
Autosave, sonst Reset). Keine automatische Konsequenz (kein Kick, keine KI-Übernahme).

**Zurückgestellt:** Regel, dass dauerhaft inaktive Spieler die „alle ready"-Sofortauflösung
nicht blockieren (z.B. ab X verpassten Runden ignoriert). Wird nachgerüstet sobald sich
Bedarf zeigt.

### 4. KI-Spieler nutzen dieselbe Order-Pipeline

KI-Slots reichen `turn_orders`-Einträge über denselben Submission-Pfad ein wie menschliche
Spieler. Kein Sonderpfad an der `TurnResolutionEngine` vorbei. Entscheidungslogik (Scoring-
Heuristiken) ist ein separates Concern; Qualität/Umfang bei Bedarf spezifizieren.

### 5. Pub/Sub-Struktur (Event-System) — sofort wirksam

Domain-Events werden von Services gefeuert. Presentation-/Notification-Logik ausschließlich
in Listenern. Die Resolution Engine kennt nur Domain-Events.

**Sofort umzusetzen (gilt für Singleplayer-Code jetzt):**
- Services dürfen keine direkten Mail-/Notification-Aufrufe enthalten.
- `TickService` und `RunProgressService` sollen Domain-Events feuern (z.B. `SolAdvanced`,
  `RunStarted`, `RunEnded`) statt Presentation-Logik direkt auszuführen.
- Event-Namen ohne `Run`-Präfix wählen, die bei späterer `games`-Migration noch passen
  (Ausnahme: bestehende interne Events können zunächst bleiben, müssen aber bei Migration
  umbenannt werden).

**Für Multiplayer ergänzen (zurückgestellt):**
`PlayerMarkedReady`, `TurnResolved`, `PlayerOrderResolutionFailed`, `GameStarted`.

### 6. RNG-Seed — sofort vorbereiten

Jedes Spiel (aktuell: jeder Run) benötigt einen persistierten `rng_seed` für deterministische
Zufallsanwendung in der Resolution Engine. Das `runs`-Modell bekommt bei nächster Gelegenheit
eine `rng_seed`-Spalte (nullable, für bestehende Runs rückwärts-kompatibel).

Alle zufallsabhängigen Operationen in Services, die heute `rand()`/`mt_rand()` nutzen, sollen
perspektivisch auf ein `SeededRng`-Objekt umgestellt werden, das den Seed als Zustand trägt.
Nicht jetzt implementieren, aber bei neuen Services berücksichtigen.

### 7. Realtime-Anbindung — Polling zuerst

Entscheidung: **Polling-first**. Bei 1–4 Spielern und turn-basiertem Spielfluss ist Polling
(kurzes Intervall beim Warten auf Mitspieler) ausreichend. Laravel Reverb / WebSocket-
Broadcasting ist zurückgestellt bis Polling sich als unzureichend erweist.

Falls Reverb später: `TurnResolved implements ShouldBroadcast`, Private Channel pro Spiel.

---

## Anpassungen gegenüber dem ursprünglichen Plan (Stand 2026-07-01)

| Plan-Punkt | Anpassung |
|---|---|
| `games`-Tabelle parallel zu `runs` | `games` ersetzt `runs` langfristig (1 Konzept) |
| `ColonizeActionResolver` in Phase 12 Tests | Leftover — ersetzt durch generischen `ExclusiveClaimResolver`-Test |
| Phase 9 Pub/Sub: nur für Multiplayer | Event-Separation gilt sofort für alle Services |
| RNG-Seed: nur in `games`-Migration | `runs` bekommt `rng_seed` jetzt als Vorarbeit |

---

## Umsetzungsreihenfolge (wenn Multiplayer aktiv angegangen wird)

1. **Migration**: `rng_seed` auf `runs` (Vorarbeit, sofort). Bei Multiplayer: `games`-Tabelle
   als Ablösung von `runs`, plus `game_players`, `turn_orders` (inkl. `placed_at_tick`-Analogie).
2. **Event-Separation**: Singleplayer-Services auf Domain-Events umstellen (kann schrittweise
   erfolgen).
3. **Order-Submission-Layer**: `SaveTurnOrderRequest`, `TurnOrderController`, Autosave-API.
4. **State Machine + Trigger**: `TurnService::startTurn()`, `PlayerMarkedReady`-Event,
   `CheckAllPlayersReady`-Listener, Scheduler für Deadline-Check + Erinnerung.
5. **Resolution Engine**: `ResolveTurnJob` (idempotent), `TurnResolutionEngine::apply()`,
   `ExclusiveClaimResolver`, `emptyOrder()`-Fallback.
6. **KI-Slots**: Listener auf `GameStarted`/`TurnService::startTurn()`, Order-Einreichung.
7. **Tests**: Unit `TurnResolutionEngine` + `ExclusiveClaimResolver`, Feature `ResolveTurnJob`
   Idempotenz/Fehlerisolation, Trigger-Pfade, Autosave-Verhalten, Missed-Turns-Zähler.

---

## Zurückgestellt (bewusst)

- Inaktiv-Sonderregel für „alle ready"-Sofortauflösung (Phase 7 — bei Bedarf nachrüsten)
- Laravel Reverb / WebSocket-Broadcasting (Phase 10 — Polling zuerst)
- KI-Entscheidungslogik im Detail (Phase 8 — Umfang separat spezifizieren)
- Vollständige `runs` → `games` Migration (bei Multiplayer-Einführung)
- Konkrete exklusive Aktionstypen als Checkliste (ergänzen wenn Mechanik-Set feststeht)

## Konsequenzen

- Singleplayer-Code läuft bis auf weiteres unverändert auf dem interaktiven Modell.
- Neue Services sollen Domain-Events statt direkter Presentation-Logik verwenden.
- `rng_seed` auf `runs` ergänzen bei nächster passender Migration.
- `TurnResolutionEngine` und `ExclusiveClaimResolver` sind bei Multiplayer-Einführung
  Greenfield-Code (kein Refactoring bestehender Services nötig, da kein Sonderpfad).
