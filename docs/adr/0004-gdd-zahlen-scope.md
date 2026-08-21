# ADR 0004: GDD-Zahlenumfang und Referenzdokumentation

**Datum:** 2026-08-21  
**Status:** Akzeptiert (Owner-Vorgabe 2026-08-18 in MEMORY.md documented)

## Kontext

GDD und `docs/game-reference.md` enthalten derzeit überlappende konkrete Zahlenwerte (Credits-Kosten, AP-Ausgaben, Supply-Caps, Decay-Raten). Dies führt zu drei Problemen:

1. **Sync-Aufwand:** Jede Balance-Änderung erfordert Updates in Config + GDD + game-reference.md — hohe Fehlerrate
2. **Wahrheitskonflikt:** CLAUDE.md sagt "Config ist canonical", aber GDD wird von Agenten oft als Prosa-Quelle behandelt, was Zahlen-Drift verursacht
3. **Dokumentations-Schwelle:** Neue Zahlen im GDD erfordern sofort Nachzug (Game Designer hat weniger Flexibilität, um schnell zu experimentieren)

## Entscheidung

**Zwei-Schichten-Modell:**

### Schicht 1: GDD-Prosa (nur Konzepte + grobe Skalierungen)
- **Was bleibt:** Mechanik-Beschreibungen, Designabsichten, Beispiel-Größenordnungen ohne feste Zahlen
- **Was raus:** Konkrete Credits-Beträge, exakte AP-Kosten, präzise Decay-Raten (alle ins Config)
- **Wie formulieren:** "kosten Credits" statt "kosten 30 Cr"; "niedrigerer Cap" statt "Cap = 10"; "deutlich höhere Kosten" statt "Kosten = 150 Rg"
- **Exception:** Run-begrenzte Ziele (z.B. "Sol 30") gehören ins GDD, da sie narrative Pacing definieren — diese sind nicht Config-Variablen, sondern Spieldesign

### Schicht 2: `docs/game-reference.md` als Zahlen-Quelle
- Listet alle aktuellen Config-Werte auf (Ressourcentabelle, Gebäude-Kosten, Decay-Raten, Supply-Boni, etc.)
- Wird manuell nach größeren Balance-Passes refreshed — **kein Auto-Sync nötig**, weil Agenten primär Config direkt lesen (Referenzdatei ist für Quick-Lookup über die Ebene hinweg)
- GDD verweist auf game-reference.md via Fußnote, wenn absolute Zahlen nötig: "siehe `docs/game-reference.md` Abschnitt Gebäude-Kosten"

### Schicht 3: Config als Single Source of Truth (unverändert)
- `config/game.php`, `config/buildings.php` bleiben die kanonischen Quellen
- Agenten lesen Config direkt bei konkreten Coding-Aufgaben
- Game Designer kann Config experimentell verändern; GDD bleibt stabil

## Konsequenzen

| Positiv | Negativ |
|---|---|
| Sync-Aufwand sinkt um ~70% (kein manueller GDD-Text-Nachzug nötig) | game-reference.md ist hand-maintained, kann aus Sync gehen wenn Game Designer vergisst zu refreshen |
| Agenten arbeiten gegen Config statt GDD für Zahlen (reduziert Verwirrung) | Playtest-Vorbereiter muss zwei Tabs offen halten (GDD + game-reference.md / oder Config) statt einer Datei |
| GDD bleibt lesbar als Designdokument (ohne Zahlen-Wust) | Erfordert Discipline — keine Ausnahmen für "schnelle Zahlen-Hardcodes im GDD" |

## Betrachtete Alternativen

### 1. Auto-generierte game-reference.md (aus Config)
**Pro:** Null Drift, immer aktuell.  
**Contra:** Braucht Artisan-Command + Test (TDD). Außerhalb project-manager-Rolle. Künftige Task für game-developer.

**Entscheidung:** Zurückgestellt. Wenn Drift zum Problem wird, diese als Ticket für game-developer aufmachen.

### 2. Nur GDD, alles hardcoded
**Pro:** Single Source of Truth, einfach.  
**Contra:** Maximale Sync-Last; Entscheidung 2026-08-18 widersprochen; GDD wird zur Wartungs-Albtraum.

**Entscheidung:** Verworfen (Owner-Feedback deutlich dagegen).

### 3. Nur Config, keine Referenzdoku
**Pro:** Null Overhead.  
**Contra:** Agenten müssen `config/buildings.php` o.ä. lesen (Code-Context), kein Quick-Reference; Playtest-Vorbereitung unbequem.

**Entscheidung:** Nicht ideal für Spielmechanik-Planung; game-reference.md bleibt als Convenience-Layer.

## Umsetzungs-Checkliste (wenn dieser ADR akzeptiert)

- [ ] GDD §3–§15 überarbeiten (Zahlen rausnehmen, grobe Formulierungen einbauen) — Ticket für game-designer
- [ ] game-reference.md als Referenz von GDD-Fußnoten verlinken — Ticket für project-manager
- [ ] CLAUDE.md mit dieser Policy updaten unter "Sprachregeln" — project-manager
- [ ] Agenten-Onboarding: "Zahlen immer aus Config lesen, nicht GDD" — in nächster CLAUDE.md-Überarbeitung

---

**Diese ADR ersetzt nicht die Entscheidung des Owners, nur dokumentiert sie.**
