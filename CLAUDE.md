# CLAUDE.md — Nouron Projektkontext

## Projekt-Überblick

**Nouron** ist ein Sci-Fi-Strategiespiel, entwickelt 2008–2014, seit 2026 wieder aktiv aufgenommen.
- GitHub: https://github.com/nouron/nouron
- Techstack: PHP, Laminas (migriert von ZF2), SQLite, REST-API, jQuery/JS-Frontend, Bootstrap 5
- Status: Migration auf Laminas + Bootstrap 5 abgeschlossen (branch `laminas-migration`), App ist grundsätzlich lauffähig

## Aktueller Stand (Stand: März 2026)

**Phase 1: ZF2 → Laminas Migration — abgeschlossen**

Folgende Arbeiten wurden bereits erledigt:

### ZF2 → Laminas Migration
- Alle Namespaces von `Zend\*` auf `Laminas\*` migriert
- `composer.json` auf Laminas-Pakete umgestellt
- PHP-8-Kompatibilität hergestellt (veraltete Konstrukte bereinigt)
- Service-Locator-Shim in `IngameController` ergänzt (Laminas hat `getServiceLocator()` aus Controllern entfernt)
- `Core\Model\ResultSet::current()` für Laminas-Kompatibilität angepasst
- `AbstractTable::save()` überarbeitet (Hydrator-Extraktion, skalare Filterung)
- Routing, Autoloading und Modul-Konfiguration auf Laminas-Standard gebracht

### Bootstrap 3 → Bootstrap 5 Migration
- Umstieg auf Bootstrap 5 per CDN (inkl. Bootstrap Icons, jQuery 3)
- Alle Glyphicons durch Bootstrap Icons (`bi bi-*`) ersetzt (~50 Stellen)
- Alle `data-toggle/data-dismiss/data-target` → `data-bs-*` Attribute aktualisiert
- Accordion, Modal, Pagination, Navbar auf BS5-Struktur umgebaut
- jQuery-Post-Processing im Layout für Laminas Navigation-Helper (fügt `nav-link`-Klassen hinzu)
- Ressourcenleiste von `row`-Layout auf `d-flex` umgestellt
- `form-group` per CSS wiederhergestellt (in BS5 entfernt), Inputs per jQuery mit `form-control` versehen

### INNN (Nachrichten & Ereignisse)
- `MessageViewFactory` korrigiert (falsche Entity-Klasse wurde eingebunden)
- `MessageService` WHERE-Klauseln auf korrekte camelCase-Spaltennamen korrigiert
- Tippfehler in Controller-Methoden behoben
- Alle Nachrichtenaktionen gegen null-userId aus Session abgesichert
- Ereignisse-View implementiert (war leeres Template)
- Leere-Liste-Hinweise in allen INNN-Tabs ergänzt

### Techtree
- SVG-Overlay blockierte alle Klicks auf Tech-Buttons → `pointer-events: none` ergänzt
- AJAX-Modal-Loading implementiert: Klick auf Tech öffnet Popup mit Serverinhalt
- Klick-Handler für Aktions-Buttons (ausbauen, reparieren, abreißen, AP investieren)
- Hover-Effekte für AP- und Status-Bars auf BS5-Klassennamen aktualisiert

### Fleet
- `fleets.js` für neue Config-UI neu geschrieben (click-to-select, Mengenbuttons)
- 500-Fehler in `getFleetTechnologies` behoben (toter ZF2-Code entfernt)

**Phase 2 (nächster Schritt): Spielablauf stabilisieren**
- Tick-System, Aktionspunkte, Handelsrouten, Flottenoperationen testen und ggf. reparieren

**Phase 3 (später): Neukonzeption "Nouron 2026"**
Nach der Stabilisierung soll das Spiel stark vereinfacht und modernisiert werden:
- Solo-basierte Spielweise mit Highscore (kein Online-Multiplayer mehr)
- Nur eine menschliche Rasse (Fraktionen möglich, aber anders als ursprünglich)
- Nur 2 Hauptressourcen: Credits und Aktionspunkte (statt 10+ Ressourcentypen)
- Kein Browsergame-MMO mehr, eher eine moderne Web-App

## Wichtige Korrekturen / Klarstellungen

- **Datenbank ist SQLite** (NICHT MySQL). Die DB-Datei liegt unter `data/db/nouron.db`
- Die Website ist eine **GitHub Page** im Repo (keine extra Sicherung nötig)
- `Routen.txt` ist vermutlich out-of-date
- `code/nouron_(pre_zend)/` ist stark veraltet und nicht mehr verwendbar
- **Nur der aktuelle Stand im GitHub Repository ist relevant**

## Datenbank-Schema (SQLite, 35 Tabellen)

### Kernstruktur

```
Galaxie:    glx_systems → glx_system_objects → glx_colonies
Spieler:    user → user_resources (Credits, Supply auf User-Ebene)
Kolonie:    colony_buildings, colony_resources, colony_researches, colony_ships, colony_personell
Flotten:    fleets → fleet_ships, fleet_resources, fleet_personell, fleet_researches
Befehle:    fleet_orders (tick-basiert, serialisierte PHP-Daten)
Handel:     trade_resources, trade_researches
Nachrichten: innn_messages, innn_events, innn_news, innn_message_types
Stammdaten: buildings, researches, ships, personell, resources + jeweilige _costs-Tabellen
```

### Ressourcen (9 Stück)

| ID | Name | Kürzel | Handelbar | Startmenge |
|----|------|--------|-----------|------------|
| 1 | res_credits | Cr | Nein | 3000 |
| 2 | res_supply | Sup | Nein | 200 |
| 3 | res_water | W | Ja | 500 |
| 4 | res_ferum | E | Ja | 500 |
| 5 | res_silicates | S | Ja | 500 |
| 6 | res_ena | ENrg | Ja | 100 |
| 8 | res_lho | LNrg | Ja | 100 |
| 10 | res_aku | ANrg | Ja | 100 |
| 12 | res_moral | M | Nein | 0 |

### Gebäude (25 Stück)

Wichtige Gebäude mit Abhängigkeitskette:
- `techs_commandCenter` (ID 25) — Basisgebäude, keine Voraussetzung, max Level 10
- `techs_oremine` (27), `techs_silicatemine` (41), `techs_waterextractor` (42) — Rohstoffgebäude, brauchen CommandCenter Lv1
- `techs_housingComplex` (28) — Wohnkomplex, braucht CC Lv3, max Level 200
- `techs_sciencelab` (31) — Forschungslabor, braucht CC Lv4
- `techs_tradecenter` (43) — Handelszentrum, braucht CC Lv5
- `techs_civilianSpaceyard` (44) — Zivile Werft
- `techs_militarySpaceyard` (68) — Militärwerft, braucht Zivile Werft Lv5
- Diverse Zivilgebäude: temple, parc, hospital, stadium, casino, prison, museum, bar, etc.
- `techs_bank` (70) — Bank
- `techs_secretOps` (66) — Geheimdienst

### Forschungen (10 Stück)

biology, languages, mathematics, medicalScience, physics, chemistry, economicScience, diplomacy, politicalScience, military

### Schiffe (7 Typen)

fighter1, frigate1, battlecruiser1, smallTransporter, mediumTransporter, largeTransporter, colonyShip

### Personal (4 Typen)

engineer (industry), scientist (civil), pilot (military), trader (economy)

### Spielmechaniken (aus DB-Analyse)

- **Tick-basiert**: fleet_orders haben ein `tick`-Feld, Events referenzieren Ticks
- **AP-System**: `locked_actionpoints` trackt AP-Verbrauch pro Tick/Kolonie/Personal
- **Gebäude-Verfall**: `status_points` in colony_buildings (max_status_points definiert Obergrenze)
- **Handelsrouten**: trade_resources/trade_researches mit Richtung (0=Kauf, 1=Verkauf), Preis, Menge, Restriktion
- **Flottenbefehle**: Serialisierte PHP-Arrays in fleet_orders.data (z.B. Trade-Parameter)
- **Beziehungssystem**: innn_message_types haben relationship_effect
- **Fraktionen**: user.faction_id (Werte 6, 7 in Testdaten)
- **Rassen**: user.race_id (Werte 1, 3 in Testdaten)
- **Koordinatensystem**: x, y für Systeme und Systemobjekte (z.B. 6800,3000)

## Architektur (Laminas MVC)

Tatsächliche Modulstruktur (11 Module):
```
module/
  Application/        -- Layout, Navigation, Basis-Routing
  Core/               -- Abstrakte Basisklassen (AbstractTable, AbstractService, IngameController)
  Colony/             -- Kolonie-Verwaltung
  Fleet/              -- Flottenoperationen und -konfiguration
  Galaxy/             -- Sternensysteme, Karte
  INNN/               -- Nachrichten, Ereignisse, News
  Map/                -- Karten-Hilfsfunktionen
  Resources/          -- Ressourcen-Tracking und -Anzeige
  Techtree/           -- Gebäude, Forschung, Schiffe, Personal
  Trade/              -- Handelsrouten
  User/               -- Benutzerprofil, Einstellungen
config/
  application.config.php   -- Modulliste und Ladereihenfolge
  autoload/
    global.php             -- DB-Adapter, Service-Factories, Tick-Config
    lmcuser.global.php     -- Auth-Konfiguration
public/
  index.php           -- Entry Point
  js/                 -- techtree.js, fleets.js, galaxy.js, trade.js, ...
data/
  db/nouron.db        -- SQLite-Spieldatenbank
vendor/               -- Composer Dependencies
composer.json
composer.lock
```

Schichtung pro Modul:
```
Controller → Service → Table (TableGateway) → Entity
```
Jede Klasse hat eine eigene Factory für Dependency Injection über den Laminas ServiceManager.

## Bekannte offene Punkte / noch nicht getestet

- Tick-System und Spielfortschritt (fleet_orders Verarbeitung)
- Aktionspunkte-Vergabe und -Verbrauch im Techtree vollständig
- Handelsrouten (Trade-Modul)
- Flottenoperationen (Bewegung, Kampf, Kolonisierung)
- Login/Registrierung (Auth-System)
- Flash-Messenger in Formularen

## Technische Hinweise (gesammelt aus Migration)

- `getServiceLocator()` in Controllern: via Shim in `Core\Controller\IngameController` verfügbar
- `getActive('user')` liefert userId aus Laminas Session-Container `activeIds`
- DB-Spaltennamen im `v_innn_messages`-View sind camelCase (`isRead`, `isDeleted`, etc.)
- `AbstractTable::fetchAll()` akzeptiert String- oder Array-WHERE-Klauseln
- `setTerminal(true)` in TechnologyController rendert ohne Layout (für AJAX-Calls)
- Bootstrap 5: Navigation-Helper braucht jQuery-Post-Processing für `nav-link`-Klassen

## Vorhandene Dokumentation (im NOURON-Ordner)

- `Nouron_Projektdokumentation_2026.pdf` — Vollständige Projektdoku (8 Abschnitte)
- `Nouron_Richtungsentscheidung.pdf` — Vergleich Classic vs. Light mit Scoring-Matrix
- `OneNote/` — 26 exportierte PDFs mit Design-Notizen aus 2012–2020
  - Wichtigste: "Supply und Aktionspunkte", "Politiksystem", "Nouron Light (V2)", "Abgrenzungen"
- `nouronzf2_dev_data.sql` — SQL-Dump (historisch, SQLite ist aktuell)
- `sql/` — 15+ historische SQL-Dumps (2008–2009)

## Testdaten in der DB

Die DB enthält Simpsons-Testcharaktere: Homer (ID 0), Marge (1), Bart (3), etc.
2 Kolonien: "Springfield" (User Bart, Planet 1) und "Shelbyville" (kein User, Planet 1)
10 Flotten, 45 Fleet Orders, bcrypt-gehashte Passwörter.

## Workflow-Hinweise

- Entwicklungsumgebung: Ubuntu unter WSL2 (Windows 11)
- Claude Code wird für die Weiterentwicklung verwendet
- Der Owner heißt Mario (tech.mario@outlook.de)

## Changelog-Pflege

Am Ende jeder Session, in der Code-Arbeit stattgefunden hat, wird ein Eintrag in `CHANGELOG.md` im Projekt-Root ergänzt. Format:

```
## YYYY-MM-DD

- Kurze Beschreibung der erledigten Aufgaben (1–3 Sätze pro Thema)
- ...
```

Der Changelog soll als Grundlage für Retrospektiven und Blog Posts dienen. Einträge werden auf Deutsch verfasst, prägnant und inhaltlich (was wurde gemacht, warum).
