# CLAUDE.md — Nouron Projektkontext

## Projekt-Überblick

**Nouron** ist ein Sci-Fi-Strategiespiel, aktiv entwickelt von ca. 2008–2014.
- GitHub: https://github.com/nouron/nouron
- Techstack: PHP, Zend Framework 2, SQLite, REST-API, jQuery/JS-Frontend
- Status: Letzter aktiver Stand ca. 2014, soll jetzt wiederbelebt werden

## Aktueller Auftrag

**Phase 1: ZF2 → Laminas Migration**
Das Ziel ist, den bestehenden ZF2-Code auf Laminas (den offiziellen ZF2-Nachfolger) zu migrieren, damit der alte Stand wieder lauffähig wird. Die Business-Logik wurde nach Clean-Code-Prinzipien gebaut und sollte weitgehend 1:1 übernehmbar sein.

**Phase 2 (später): Neukonzeption "Nouron 2026"**
Nach der Migration soll das Spiel stark vereinfacht und modernisiert werden:
- Solo-basierte Spielweise mit Highscore (kein Online-Multiplayer mehr)
- Nur eine menschliche Rasse (Fraktionen möglich, aber anders als ursprünglich)
- Nur 2 Hauptressourcen: Credits und Aktionspunkte (statt 10+ Ressourcentypen)
- Kein Browsergame-MMO mehr, eher eine moderne Web-App

## Wichtige Korrekturen / Klarstellungen

- **Datenbank ist SQLite** (NICHT MySQL). Die DB-Datei `nouron.db.sqlite` liegt im Repo
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

## Architektur-Erwartungen (ZF2)

Typische ZF2-Projektstruktur:
```
module/
  Application/        -- Basis-Modul
  NouronCore/         -- oder ähnlich, Kern-Spiellogik
    config/
    src/
      Controller/     -- REST-Controller
      Model/          -- Entities / Domain Models
      Service/        -- Business Logic (Clean Code)
      Mapper/         -- Data Mapper / Repository Pattern
    view/
config/
  autoload/
    global.php
    local.php
public/
  index.php           -- Entry Point
  js/, css/, img/
data/
  nouron.db.sqlite    -- Spieldatenbank
vendor/               -- Composer Dependencies
composer.json
composer.lock
```

## Laminas-Migration: Strategische Hinweise

1. **Laminas ist der direkte ZF2-Nachfolger** — Namespace-Änderung von `Zend\*` → `Laminas\*`
2. Es gibt ein offizielles Migrationstool: `laminas/laminas-migration`
3. Schritte:
   - `composer.json` analysieren: ZF2-Dependencies identifizieren
   - `laminas-migration` Tool laufen lassen (automatischer Namespace-Tausch)
   - `composer.json` aktualisieren (zendframework/* → laminas/*)
   - Testen ob die App startet
   - SQLite-Konfiguration prüfen/anpassen
   - PHP-Version-Kompatibilität prüfen (Code war PHP 5.x/7.0, Laminas braucht 7.3+)
4. Die Business-Logik (Services, Models) sollte framework-agnostisch sein und kaum Änderungen brauchen
5. Controller und Config-Dateien werden die meisten Anpassungen brauchen

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
- Claude Code wird für die eigentliche Migration verwendet
- Der Owner heißt Mario (tech.mario@outlook.de)
