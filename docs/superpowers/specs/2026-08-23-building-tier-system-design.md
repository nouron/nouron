# Gebäude-Ausbaustufen statt Level — Design

Stand: 2026-08-23. Brainstorming-Ergebnis, Struktur-Entscheidung — **keine Zahlen** (ADR 0004), Zahlen-Kalibrierung folgt nach Playtest als separater Task.

## Ausgangslage

Fast alle Nicht-CC-Gebäude waren zwischen 2026-07-20 und 2026-08-03 bereits auf niedrige `max_level`-Werte gedeckelt (siehe `docs/gdd-config-audit.md`), aber als glatte Kostenkurve mit stillen Prozent-Effekten. Frage aufgeworfen: passt "Level" als Konzept noch, wenn ohnehin fast nichts mehr unbegrenzt wächst? Zusatzfrage: passt eine glatte Kurve überhaupt zum Rogue-Lite-Charakter (Run 60-95 Sole, konkretes Ziel + Ende), oder sind diskrete, benannte Meilensteine idiomatischer?

**Antwort:** Ja — Rogue-Lites arbeiten mit spürbaren Entscheidungsmomenten, nicht mit langen Zahlen-Kurven. Hangar macht das bereits vor (Lv1/2/3 = Drohne/Frachter/Korvette, keine Prozentkurve).

## Kernentscheidung

- **Command Center (CC) bleibt „Level"** — einziger echter Meta-Progressions-Motor eines Runs, Ausnahme vom neuen Modell.
- **Alle anderen Gebäude** werden konzeptionell zu **Ausbaustufen** mit benannten, diskreten Effekten statt einer glatten Kurve. Max. 3 Stufen (Konsistenz mit den bereits gedeckelten securityHub/uplinkStation/tradingPost), Ausnahme sciencelab (bleibt bei 3 Kenntnis-Gate-Stufen + neue Stufen IV/V, s.u.).
- **Datenmodell:** keine DB-Schema-Änderung. `level`-Spalte bleibt, nur niedriger gedeckelt. `config/buildings.php` bekommt pro Gebäude eine `tiers`-Struktur (Name, ggf. Beiname, Effekt-Beschreibung je Stufe) statt/neben der glatten Kostenkurve. Details der Config-Struktur sind Implementierungs-Task, nicht Teil dieser Spec.
- **Scope:** alle 9 Nicht-CC-Gebäude werden umgestellt (nicht nur die 3 ursprünglich offenen `max_level=null`-Fälle) — Owner-Entscheidung, da das Balancing der bisherigen Level ohnehin nie richtig durchdacht wurde.

## Namensmuster

„**Basisname + Stufe (röm. Zahl) + Beiname**" — z.B. „Krankenstation III – Vollausstattung".

**Regel: Beiname nur bei echtem Fähigkeits-Sprung, nicht bei reiner Mengensteigerung.** Wenn eine Stufe nur eine größere Zahl liefert (mehr Ertrag, mehr Kapazität, mehr Angebote), bleibt es bei „Gebäudename + römische Zahl" ohne erfundenen Namen — vermeidet aufgeblasene Namen ohne mechanischen Mehrwert.

**Skalen-Vorgabe:** Kolonie ist klein (Handvoll bis wenige Dutzend Kolonisten, kein Stadt-/Konzern-/Flottenmaßstab). Namen müssen sich wie "kleiner Außenposten, der sich mühsam ausbaut" anfühlen, nicht wie wachsende Stadt/Militär/Großkonzern. Kein militärisches Vokabular (Owner-Vorgabe, Anlass: „Lazarett" abgelehnt).

## Klassifizierung pro Gebäude

| Gebäude | Einstufung | Namensbehandlung |
|---|---|---|
| **Hangar** | Fähigkeitssprung jede Stufe (neue Schiffsklasse: Drohne/Frachter/Korvette) | Beiname bei I, II, III |
| **Sicherheits-Hub** | I/II = Mengensteigerung (Trust-Bonus-Größe), III = neue Fähigkeit (Recycling-Effekt, aktuell konfiguriert aber nie im Code gelesen — muss bei Umsetzung endlich verdrahtet werden) | Beiname nur bei III |
| **Krankenstation** | I/II = Mengensteigerung (Seuchenrisiko-Reduktion), III = Fähigkeits-Abschluss (erreicht den Wirkungsdeckel `plague_risk_reduction_cap` exakt — danach keine Überinvestition mehr möglich) | Beiname nur bei III |
| **Agrardom** | I/II = Mengensteigerung (mehr Organika), III = qualitativ neu (Puffer gegen einen Hunger-Tick-Ausfall, nicht nur mehr Ertrag) | Beiname nur bei III |
| **Wohnhabitat** | reine Mengensteigerung (mehr Wohnkapazität/Supply-Beitrag), keine neue Fähigkeit auf keiner Stufe | Keine Beinamen, alle Stufen nackt nummeriert |
| **Cantina** | reine Mengensteigerung (mehr/länger gültige Angebote) | Keine Beinamen |
| **Handelsposten** | **neu designt als Fähigkeitssprung** (s.u.) — jede Stufe schaltet einen neuen Rabatt-Kanal frei | Beiname bei I, II, III |
| **Analytik-Labor** | I-III unverändert (Kenntnis-Gates, bestehende Funktion, nicht Teil dieser Umbenennung), IV/V neu als Mengensteigerung (AP-Kosten-Rabatt-Prozentsatz) | Keine Beinamen, durchgängig nackt nummeriert (auch IV/V) |
| **Uplink-Station** | I = Fähigkeit (schaltet Nexus-Bestellungen überhaupt erst frei), II = Mengensteigerung (Tiefenscan-Kosten -1 AP), III = **zurückgestellt**, s.u. | Beiname bei I, II nackt, III bleibt unbenannt/unverändert bis Folge-Design |

## Neue Mechaniken (schließen echte Implementierungslücken, keine reine Umbenennung)

### Handelsposten (tradingPost) — alle 3 Stufen neu

Bisher: komplette leere Hülle, `merchant_price_bonus=0.12` wird von keinem Code gelesen (verifiziert). Drei bereits bestehende, unabhängige Handelskanäle: `MerchantService` (Reisender Händler), `BarService` (Cantina-Zufallsangebote), `CorporateContactService` (Nexus/Orin, selten + groß).

**Design:** Rabatt schaltet sich kanalweise frei, aufsteigend nach Seltenheit/Betragsgröße:
- Stufe I: Rabatt auf Cantina-Zufallsangebote (häufig, klein — günstigster Einstieg, lehrt die Mechanik billig)
- Stufe II: + Rabatt auf Reisenden Händler (mittlere Häufigkeit)
- Stufe III: + Rabatt auf Nexus/Corporate-Contact-Käufe (selten, aber groß — Krönung für die größte Spätinvestition)

**Zu beachten bei Umsetzung:** GDD (Zeile 468) markiert bereits, dass der Rabatt sich NICHT mit dem Konsul-Rang-Handelsbonus stapeln darf — gilt jetzt für alle drei Kanäle, nicht nur den ursprünglich gemeinten einen (additiv statt multiplikativ, oder Diminishing Returns — Zahlenfrage, kein Struktur-Thema).

Design-Review (game-designer, 2026-08-23): freigegeben, kein Powercreep-Risiko (Rabatte auf bestehende, bereits gedeckelte Angebote), sauberer Fähigkeits-Sprung pro Stufe, geringes Umsetzungsrisiko (dockt an drei bestehende Services an, keiner muss umgebaut werden).

### Analytik-Labor (sciencelab) — neue Stufen IV/V

Bisher: Lv1-3 sind reine Kenntnis-Freischalt-Gates (Lv1→5 Kenntnisse, Lv2→+geology, Lv3→+defense), Lv4/5 haben keinerlei Wirkung — echte, unbeabsichtigte Design-Lücke (bereits in GDD Zeile 1761 als offene Idee vermerkt, nie umgesetzt).

**Design:** Domänen-Effizienzbonus „Wissen" — Lv4/5 senken die AP-Kosten für Kenntnis-Levelups, analog zu den bereits implementierten `ap_cost_reduction_per_lv`-Effekten der Kenntnisse `construction`/`cartography`/`trade` (§13.3). Rührt an nichts, was pro Run gezogen wird (Roguelike-Variabilität §10 bleibt unangetastet) — reine Effizienzsteigerung auf dem, was man hat.

**Explizit verworfen:** Owner-Erstvorschlag (Lv4 = 1 Extra-Kenntnis über die Run-Ziehung hinaus, Lv5 = kompletter Baum erreichbar) — würde die bewusste Roguelike-Variabilität (§10, "analog zum variablen Spielfeld bei Catan") strukturell aufweichen. Game-designer-Review bestätigte das Owner-Bauchgefühl; die Effizienzbonus-Alternative war bereits im GDD skizziert und ist die sauberere Lösung.

### Uplink-Station Stufe III — zurückgestellt

Bisher: Code-TODO ("Run-Completion-Aktion... wenn Run-End-Mechanik existiert") — kein verwaister Kommentar, sondern eine im GDD (Zeile 450) bereits designte Idee ("Kolonialbericht senden → Meta-Bonus für nächsten Run"), die auf die noch fehlende Run-Ende-Mechanik (§15 N4) wartet.

Zwei geprüfte, aber verworfene Alternativen für heute:
1. Der bestehende Meta-Bonus selbst umsetzen — blockiert an der fehlenden Run-Ende-Mechanik, kein Struktur-Thema für heute.
2. Garantierter Mindest-Kontakt zu Orin (`CorporateContactService`) — würde die bewusste "keine garantierte Zweitinstanz"-Zufallsphilosophie (GDD Zeile 827) aufweichen, ohne dass das bewusst als Grundsatzentscheidung getroffen wurde.

**Owner-Entscheidung:** Beide Optionen erstmal nicht. Owner befürwortet grundsätzlich einen **Meta-Progressions-Mechanismus** (etwas, das über Runs hinweg freigeschaltet bleibt — roguelike-typisch), das braucht aber einen eigenen Design-Sprint. **Uplink-Station Stufe III bleibt bis dahin unverändert** (aktueller TODO-Zustand, kein neuer Code, keine neue Benennung). Separater Backlog-Punkt, nicht Teil der aktuellen Umsetzung.

## Beinamen (Entwurf, content-writer-Review ausstehend)

Erste Runde vom content-writer geliefert, aber zu großmaßstäblich für eine kleine Kolonie (z.B. „Wohnviertel", „Patrouillenhalle", „Handelsknoten", „Güterknoten" — Stadt-/Flotten-/Konzern-Sprache). **Zweite Runde nötig** mit:
- Skalen-Korrektur (kleiner Außenposten statt wachsende Stadt)
- Nur noch für die laut Tabelle oben tatsächlich benannten Stufen (Hangar I-III, Sicherheits-Hub III, Krankenstation III, Agrardom III, Uplink-Station I, Handelsposten I-III)
- Handelsposten-Namen müssen die neue Kanal-Bedeutung widerspiegeln (I=Cantina-Rabatt, II=+Reisender-Händler-Rabatt, III=+Nexus-Rabatt), nicht generische "Handelsposten wird größer"-Namen

## Offene Folge-Tasks (nicht Teil dieser Spec)

1. Content-writer: zweite Namensrunde mit obiger Korrektur.
2. Konkrete `config/buildings.php`-Struktur für `tiers` entwerfen (Implementierungs-Task, db-migration-agent/backend-coder).
3. Zahlen-Kalibrierung für alle Effekte nach Playtest (wie bei jedem anderen Balance-Thema im Projekt — siehe `docs/gdd-balance-checklist.md`).
4. securityHub `recycle_pct`-Effekt tatsächlich im Code verdrahten (aktuell konfiguriert, nie gelesen — wird mit Stufe III relevant).
5. tradingPost-Rabatt-Stacking-Regel mit Konsul-Handelsbonus definieren (additiv/Diminishing Returns).
6. Separater Design-Sprint: Meta-Progressions-Mechanismus über Runs hinweg (Uplink-Station III hängt daran, evtl. weitere Kandidaten).
7. GDD-Kapitel §4 (Gebäudebeschreibungen) + §13 (Kostenkurve) entsprechend umschreiben, sobald Struktur+Namen final sind.
