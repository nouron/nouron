# ADR 0005: Produktions-Datenbank und Hosting (Closed Beta)

**Datum:** 2026-10-02
**Status:** Akzeptiert (Owner-Entscheidungen 2026-10-02 und 2026-10-03; Annahmen des Assistenten sind als „Annahme / zu prüfen" markiert)
**Bezug:** ROADMAP R3–R7, R5b, R20; Owner-Vorgabe 2026-10-01 „Release zunächst über Laravel Cloud"

## Kontext

Nouron läuft in Entwicklung und Tests auf SQLite. Für die Closed Beta (ca. 10–20 Tester, reiner Singleplayer, ein aktiver Run pro Spieler) muss eine Produktionsumgebung gewählt werden. Geplante Plattform ist **Laravel Cloud**. Die DB-Wahl (R5) bestimmt Backup-Verfahren (R6), Cache-/Session-Store (R3) und den Migrationsaufwand.

**Stand-Hinweis zur Recherche:** Alle Plattformangaben stammen aus der Laravel-Cloud-Dokumentation vom 2026-10-02. Laravel Cloud ändert Preise und Pläne häufig; Preise und Limits vor der Entscheidung auf der Preisseite gegenprüfen. Unsichere Angaben sind mit **[UNSICHER]** markiert.

## Recherche: Laravel Cloud (Stand 2026-10-02)

| Thema | Befund |
|---|---|
| **SQLite** | **In Produktion nicht tragfähig.** Das Dateisystem jeder Umgebung ist ephemer: Deployments setzen es zurück, jedes Replica hat ein eigenes Dateisystem, Platz = 512 MB je 1 GB RAM. Laravel empfiehlt Serverless Postgres oder Laravel MySQL. Persistente Dateien nur über Laravel Object Storage (S3-artig, kein Block-Volume für SQLite). |
| **Managed DBs** | (a) **Laravel MySQL** (Flex = Scale-to-Zero, Pro = always-on; Storage 5–1000 GB, automatisches Hochskalieren), (b) **Laravel Serverless Postgres** (Neon-basiert, Autoscaling, Scale-to-Zero, PgBouncer), (c) Amazon RDS (Private Cloud, für die Beta irrelevant). `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite` sind als PHP-Extensions vorhanden. |
| **Backups MySQL** | Tägliche automatische Snapshots (Fenster 3–6 Uhr EDT), Aufbewahrung 1–30 Tage (Default 7), manuelle Snapshots jederzeit. **Restore nur in einen neuen Cluster** (nicht in-place); Download als `mysqldump` nur über Umweg (Restore in Temp-Cluster, Public Endpoint, dump). Backup-Speicher wird zum Storage-Preis abgerechnet (0,10 USD/GB-Monat US, 0,12 sonst). Binlogs ca. 8 Tage, Point-in-Time-Recovery wird als Folge erwähnt, aber **nicht als bedienbares Feature dokumentiert [UNSICHER]**. |
| **Backups Postgres** | Automatische Backups + **Point-in-Time-Recovery**, Aufbewahrungsdauer pro Cluster einstellbar; Preis Backup-Verlauf 0,35 USD/GB-Monat. |
| **Preise (Plan)** | Plan-Basis: Starter 5 USD/Monat (inkl. 5 USD Guthaben; Seiten widersprechen sich teils: andere Quelle nennt „keine Grundgebühr" **[UNSICHER]**), Growth 20 USD, Business 200 USD, plus nutzungsabhängig. Starter: 1 Umgebung/1 DB/1 Cache, Hibernation, Task Scheduler + Background-Prozesse, Custom Domains, Log-Retention **1 Tag**, DB-Snapshots 30 Tage. Growth: Log-Retention 7 Tage, Basic-WAF, Preview-Umgebungen, HTTP-Basic-Auth. |
| **Preise (Compute)** | Flex (Scale-to-Zero) 512 MB ca. 6 USD/Monat, 1 GB ca. 12 USD/Monat bei Dauerbetrieb (US-East); Pro ab ca. 32 USD/Monat. Hibernation reduziert Kosten bei Leerlauf, erster Request nach dem Schlafen ist langsamer. |
| **Preise (DB)** | Postgres: Compute 0,102 USD je Compute-Unit-Stunde (sekundengenau, 0 beim Schlafen), Storage 0,35 USD/GB-Monat. MySQL Flex/Pro: Compute-Preise je Größe **nicht erhoben [UNSICHER]**, Storage ca. 0,10 USD/GB-Monat (US). |
| **Kostenschätzung Beta** | Grob **20–40 USD/Monat** (Starter/Growth-Basis + 1 Flex-Compute + kleine DB, Daten << 1 GB) **[UNSICHER, Schätzung, nicht gerechnet]**. Stark abhängig von Scale-to-Zero-Verhalten bei 10–20 Testern mit unregelmäßigen Sitzungen. |
| **Deploy-Flow** | GitHub-Anbindung, Build-Commands (`composer install --no-dev && npm run build`, `config:cache` im Build), Deploy-Commands (hier `php artisan migrate --force`; Dateisystemänderungen im Deploy-Schritt werden nicht übernommen), Env-Variablen im Dashboard (`.env` wird zur Laufzeit geschrieben), DB-Zugangsdaten werden automatisch injiziert (`DB_HOST`, `DB_DATABASE`, ...). Einmal-Kommandos über den Commands-Tab (z. B. `user:reset-password`, Owner-Admin anlegen). Build/Deploy-Timeout je 15 Min. |
| **HTTPS/Domain** | Kostenlose `*.laravel.cloud`-Domain, Custom Domain mit automatischem SSL-Zertifikat. |
| **Scheduler/Queue** | Task Scheduler und Background-Prozesse im Starter enthalten; Worker-Cluster erst ab Growth. Nouron braucht beides vorerst nicht (`QUEUE_CONNECTION=sync`, Sol-Tick über `/sol/next`). |
| **Logs/Monitoring** | Dashboard-Logs mit kurzer Retention (Starter 1 Tag, Growth 7, Business 30); Log-Drains nur Enterprise. Integrierte **Laravel Nightwatch**-Anbindung (`composer require laravel/nightwatch`, Token im Dashboard; Exceptions, Requests, Logs, Queries; Standard-Retention 90 Tage). **Nightwatch-Preise/Gratis-Kontingent nicht erhoben [UNSICHER].** |
| **Mail** | Kein eigener Mail-Dienst im Rahmen dieser Recherche gefunden **[UNSICHER]**; üblich: externer SMTP/API-Anbieter (Resend, Postmark, SES) über `MAIL_*`-Variablen. Für die Beta nicht nötig (R10/R20). |
| **Persistenter Storage** | Nur Object Storage; Nouron hat keine Nutzer-Uploads, daher unkritisch. `storage:link` entfällt. |

## Recherche: Repo-Kompatibilität (nur gelesen)

Grep über `app/`, `database/`, `config/`, `data/sql/`, Stand master 32b1ec7c:

1. **Migrationen (größter Posten).** 131 Migrationsdateien; 38 verwenden `DB::statement`/`unprepared`, 27 davon SQLite-spezifisch (`PRAGMA foreign_keys`, `INTEGER PRIMARY KEY AUTOINCREMENT`, Tabelle-neu-anlegen-und-umbenennen, z. B. `2026_05_24_000002_add_phase_to_runs`, `2026_06_11_111449_make_system_object_id_nullable_on_glx_colonies`, `2026_06_20_000003_remove_galaxy_fleet`). Diese Migrationen laufen auf MySQL/Postgres **nicht**. Betroffen sind auch Migrationen, die Daten per SQL befüllen oder Views anlegen.
2. **View `v_glx_colonies`** (Model `Colony` liest daraus, `MerchantService` per `DB::table`). Join-View über `glx_colonies` und `glx_system_objects` (`CREATE VIEW … JOIN`) — portables SQL; die Baseline legt sie mit an. Weitere Views (`v_trade_resources`, ggf. Altlasten aus `0001_01_01_999999_create_views`) prüfen.
3. **Raw-SQL im App-Code:** `MAX(0, amount - x)` in `HangarService:751` ist die SQLite-Skalarfunktion — auf MySQL/Postgres ist das `GREATEST(0, ...)` (MySQL: Fehler/Aggregat-Fehlinterpretation, Postgres: Fehler). `orderByRaw('CASE id WHEN ...')`, `SUM(CASE ...)`, `COALESCE`, `LOWER(...)` sind portabel. `DB::raw('amount + N')`-Updates in `GameTick` sind portabel.
4. **`insertOrIgnore`** (`ColonyTileService`, `CharacterCodexService`, `BarService`, `ColonySeedDemo`): Laravel übersetzt dies pro Treiber (MySQL `INSERT IGNORE`, Postgres `ON CONFLICT DO NOTHING`) — portabel, setzt aber echte Unique-Indizes voraus (bekannte Lücke: `advisors_colony_personell_unique`, siehe Memory).
5. **Testdaten:** `data/sql/testdata.sqlite.sql` wird von `TestSeeder` zeilenweise per Regex gefiltert und als `INSERT OR REPLACE INTO` ausgeführt — **SQLite-Syntax**. Auf MySQL (`REPLACE INTO`) und Postgres (`ON CONFLICT`) nicht lauffähig. Zusätzlich auf Postgres: explizite IDs verschieben die Sequenzen (Sequenz-Reset nötig), strengere Typen (Text-/Integer-Vergleiche, Booleans). Die Tests hängen an festen IDs (Bart user_id=3, colony_id=1, ...).
6. **Test-Infrastruktur:** `phpunit.xml` erzwingt `sqlite` + `:memory:`; Playtest-Bot startet Process-Pool-Kinder mit eigenen DB-Dateien. Beides ist durch die Entscheidung (ein Dialekt, MySQL) betroffen und wird in R5b umgestellt: Suite und CI laufen gegen MySQL, der Bot nutzt eine gemeinsame DB mit einem User pro Lauf (siehe „Entscheidung“).
7. **Gleichzeitige Schreibzugriffe:** `/sol/next` sperrt pro Run über `Cache::lock` (R11). SQLite hat nur einen Schreiber (Datei-Lock); bei 10–20 Testspielern mit kurzen Transaktionen unkritisch, aber `busy_timeout`/`journal_mode` stehen in `config/database.php` auf `null`. MySQL/Postgres verhalten sich bei parallelen Runs besser (Zeilen-Locks), `lockForUpdate` bleibt dort wirksam (in SQLite ein No-Op).

## Optionen

| Kriterium | A: SQLite auf eigenem VPS (WAL + Litestream/Cron-Backup) | B: Laravel Cloud + Laravel MySQL | C: Laravel Cloud + Serverless Postgres |
|---|---|---|---|
| **Betriebsaufwand** | Hoch: Server, PHP, Webserver, TLS, Updates, Deploy-Skript (R4) selbst | Niedrig: Plattform deployt, TLS, Logs, Nightwatch | Niedrig (wie B) |
| **Migrationsaufwand Code** | Keiner | Mittel: Migrations-Baseline neu (s. u.), `MAX`→`GREATEST`, CI-Job gegen MySQL | Mittel bis hoch: wie B plus Typstrenge, Sequenzen, ggf. Boolean-/Cast-Anpassungen |
| **Backup** | Selbst gebaut (Litestream auf S3 oder Cron + `sqlite3 .backup`); Restore selbst testen | Tägliche Snapshots 1–30 Tage, Restore in neuen Cluster; kein dokumentiertes PITR **[UNSICHER]** | Automatische Backups + PITR, Retention einstellbar |
| **Parallele Schreibzugriffe** | Ein Schreiber; für 10–20 Tester ausreichend | Zeilen-Locks, voll parallel | Zeilen-Locks, voll parallel |
| **Kosten (Beta)** | ca. 5–10 EUR/Monat VPS **[UNSICHER, Schätzung]** | ca. 20–40 USD/Monat Gesamt **[UNSICHER]** | ähnlich B; Compute nur während Aktivität, Storage teurer (0,35 USD/GB) **[UNSICHER]** |
| **Passt zu Owner-Vorgabe Laravel Cloud** | Nein | Ja | Ja |
| **Zukunft (Skalierung, mehr Tester)** | Wechsel später nötig | Direkt skalierbar | Direkt skalierbar |

Option „SQLite auf Laravel Cloud" entfällt (ephemeres Dateisystem, Datenverlust bei jedem Deploy).

## Entscheidung (Owner, 2026-10-02)

**Hosting auf Laravel Cloud, Region EU-Frankfurt, Datenbank Laravel MySQL (Option B).**

Owner-Entscheidungen:

- **Hosting:** Laravel Cloud, Region EU-Frankfurt.
- **Datenbank:** Laravel MySQL. Postgres (Option C) und VPS mit SQLite (Option A) sind **verworfen**. Grund für den Verzicht auf den VPS: Der Owner will so wenig wie möglich selbst administrieren.
- **Ein Dialekt:** Zwei Dialekte (SQLite lokal, MySQL Prod) sind nicht gewünscht. Lokal, CI und Produktion laufen alle auf MySQL.
- **Tests, CI und Playtest-Bot (2026-10-03):** Die Test-Suite läuft gegen MySQL, nicht gegen SQLite-in-memory (Best Practice: dieselbe Engine wie Produktion; sonst kehrt der zweite Dialekt durch die Hintertür zurück). MySQL hat keinen echten In-Memory-Modus; bei Bedarf wird erst gemessen, dann das Datenverzeichnis auf tmpfs gelegt und die Durability abgeschaltet.
- **Kein Docker lokal (YAGNI):** MySQL wird nativ in WSL installiert (eine Instanz, drei Datenbanken `nouron`, `nouron_test`, `nouron_playtest`). In CI genügt der MySQL-Service von GitHub Actions.
- **Playtest-Bot wie echte Spieler:** Die Bots laufen parallel in **einer gemeinsamen** Datenbank (`nouron_playtest`), jeder Lauf legt einen eigenen User samt Kolonie an — dasselbe Mehrspieler-Modell wie in Produktion (Voraussetzung: R18/R19, Tick und Zugriffe pro Run). Das ersetzt die frühere Isolation über `:memory:` pro Prozess und ist zugleich ein Mehrspieler-Lasttest.
- **Datensicherheit:** Bis zu 24 h Datenverlust sind akzeptabel (tägliche Snapshots genügen, kein PITR nötig).
- **Budget:** 20 € pro Monat; Scale-to-Zero (App und DB) ist erlaubt.
- **Daten:** Frische Produktions-DB, keine Dev-/Testdaten, keine Test-Accounts.
- **Domain:** `app.nouron.de` für die App; `www.nouron.de` bleibt Landingpage.

Begründung:

- SQLite ist auf Laravel Cloud technisch nicht nutzbar (ephemeres Dateisystem); der Owner will keinen eigenen Server betreiben.
- MySQL ist dem bisherigen SQLite-Verhalten (nachsichtigere Typen) näher als Postgres; der Portierungsaufwand ist geringer.
- Der spätere lokale Singleplayer ist ein **Rewrite** (SvelteKit + Browser-DB, nicht PHP) und wird von der Prototyp-DB der Beta nicht eingeschränkt. Die DB-Wahl hier betrifft nur den gehosteten Beta-Prototyp.

### Annahme / zu prüfen (Assistent, keine Owner-Entscheidung)

1. **Migrations-Squash:** Da Produktion frisch ist, wird das Endschema als **eine** Baseline-Migration (`Schema::create`, kein `schema:dump`, damit Produktion kein `mysql`-Kommandozeilenprogramm braucht) erzeugt statt 131 Migrationen (davon 27 SQLite-spezifisch) einzeln zu portieren. Zu prüfen: Äquivalenz der Baseline zum heutigen Schema (Spalten inkl. Reihenfolge, Indizes, Fremdschlüssel, Views) — im Umsetzungsplan als maschineller Vergleich vorgesehen. Die Baseline fügt **keine** Daten ein.
2. **Stammdaten vs. Fixtures:** `data/sql/testdata.sqlite.sql` mischt heute Stammdaten (Ressourcen, Gebäude, Kosten, Berater-/Schiffs-/Forschungsdaten) und Test-Fixtures (Simpsons-User, Kolonien). Annahme: Aufteilung in `masterdata.sql` (auch Produktion) und `testdata.sql` (nur Tests/Dev), beide in dialektneutraler Syntax (`REPLACE INTO`, unquotierte Bezeichner). `REPLACE INTO` löscht und fügt neu ein und ist daher nur für eine **leere** DB zulässig; der Stammdaten-Seeder schreibt nichts, wenn bereits Daten existieren (sonst Foreign-Key-Fehler bzw. Löschen von Spielerdaten).
3. **Budget-Risiko:** Die Kostenschätzung von 20–40 USD/Monat war unsicher und **kann über 20 € liegen**. Vor Buchung Preise live prüfen (Plan-Basis, MySQL-Compute, Scale-to-Zero-Verhalten). Der Starter-Plan hat nur **1 Tag Log-Retention**; Growth (7 Tage) kostet mehr.

### Folgearbeiten (Roadmap)

1. **R5b MySQL-Portierung** (Aufwand Groß, TDD; Plan: `docs/superpowers/plans/2026-10-03-r5b-mysql-portierung.md`): lokale MySQL-Umgebung, `MAX(0, …)` in `HangarService` portabel, Stammdaten/Fixtures trennen, Migrations-Baseline, Suite und CI gegen MySQL, Playtest-Bot in gemeinsamer DB.
2. **R4 Deployment:** Laravel Cloud, Domain `app.nouron.de`, Build-/Deploy-Commands, Owner-Admin per Kommando. `DatabaseSeeder` verweigert Produktion bereits (R3).
3. **R6 Backups:** Laravel-Cloud-Snapshots (täglich), Aufbewahrung konfigurieren, Restore einmal in einen neuen Cluster testen, Export-Weg (`mysqldump` über Public Endpoint) dokumentieren.
4. **R7 Monitoring:** siehe unten (offen).
5. Cache-Store `database` (R3) und Sessions `database` nutzen die MySQL-Instanz mit; bei 10–20 Testern vernachlässigbares Binlog-Volumen.
6. R20 (Mail-Reset) separat: externer Mail-Anbieter nötig.

## Konsequenzen

- (+) Kein Servermanagement, automatische TLS-Zertifikate, Deploy per Git.
- (+) Echte Parallelität (Zeilen-Locks) statt Einzelschreiber; `lockForUpdate` wirkt.
- (+) Ein Dialekt über alle Umgebungen, keine Drift zwischen Dev/CI/Prod.
- (−) Einmaliger Portierungsaufwand (R5b, Groß); lokal wird ein MySQL-Server nötig, ggf. langsamere Tests.
- (−) Laufende Kosten und Plattformabhängigkeit; Preise ändern sich, Budget 20 € ist nicht gesichert **[UNSICHER]**.
- (−) Scale-to-Zero: erster Request nach Leerlauf langsamer (App-Hibernation und DB-Wake); bei unregelmäßigen Sitzungen spürbar.
- (−) Restore nur in neuen Cluster: manueller Ablauf mit Umschalten der DB-Anbindung (Deploy nötig); kein dokumentiertes PITR, bis zu 24 h Verlust.

## Betrachtete Alternativen

- **A: SQLite auf eigenem VPS** (WAL + Litestream/Cron-Backup) — verworfen: hoher Betriebsaufwand (Server, TLS, Updates, Deploy), Owner will so wenig wie möglich selbst administrieren.
- **C: Laravel Serverless Postgres** (PITR, Neon-basiert) — verworfen: höherer Portierungsaufwand (Typstrenge, Sequenzen), PITR für die Beta nicht nötig.
- **SQLite auf Laravel Cloud** — technisch ausgeschlossen (ephemeres Dateisystem).
- **Zwei Dialekte** (SQLite lokal/Tests, MySQL Prod) — vom Owner nicht gewünscht.

## Offene Punkte

- **R7 Monitoring: Nightwatch vs. Sentry — offen.** Der Owner kannte beides nicht. *Laravel Nightwatch* ist das Monitoring von Laravel selbst, in Laravel Cloud integriert (Exceptions, Requests, Queries, Logs; Standard-Retention 90 Tage; Preis/Gratis-Kontingent nicht erhoben). *Sentry* ist ein unabhängiger, weit verbreiteter Fehler-Tracker mit E-Mail-Benachrichtigung bei neuen Exceptions, plattformunabhängig (auch nach einem späteren Hosterwechsel nutzbar). Entscheidung vor Umsetzung von R7; Kriterien: Kosten innerhalb des 20-€-Budgets, Aufwand, Benachrichtigungsweg.
- Annahmen 1–3 oben bestätigen bzw. prüfen (Squash, Test-Suite-Dialekt, Preise vor Buchung).
- Quellen-Preisangaben sind mit **[UNSICHER]** markiert und vor der Buchung gegenzuprüfen.

## Quellen (abgerufen 2026-10-02)

- Laravel Cloud Preise: https://laravel.com/cloud/docs/pricing
- Laravel MySQL (Backups, Scale-to-Zero, Binlogs): https://laravel.com/cloud/docs/resources/databases
- Serverless Postgres: https://laravel.com/cloud/docs/resources/databases/postgres
- Umgebungen (Build/Deploy, Dateisystem, Domains, Nightwatch): https://laravel.com/cloud/docs/environments
- SQLite-Support (ephemer, nicht empfohlen): https://laravel.com/cloud/docs/knowledge-base/sqlite
- Nightwatch: https://nightwatch.laravel.com/
- Laravel Cloud Überblick/Ressourcen: https://laravel.com/cloud/app-resources
