# Produktions-Datenbank: Deploy-Ablauf

Kurzanleitung für die Datenbank der Closed Beta auf Laravel Cloud (Laravel MySQL, EU-Frankfurt). Hintergrund und Entscheidungen: `docs/adr/0005-produktions-datenbank-und-hosting.md`. Der Rest des Deployments (Domain, Env-Variablen, Build) entsteht in R4.

## Deploy-Commands

Jeder Deploy führt aus:

```bash
php artisan migrate --force
php artisan db:seed --class=ReferenceDataSeeder --force
```

- **Erster Deploy** auf der frischen Produktions-DB legt das Schema an (Baseline-Migration `0001_01_01_000000_baseline.php`) und füllt die Referenzdaten (Ressourcen, Gebäude, Kosten, Berater, Forschung, Schiffe).
- **Spätere Deploys** sind idempotent: `migrate` führt nur neue Migrationen aus, `ReferenceDataSeeder` aktualisiert die Referenzzeilen per Upsert (und wendet danach `game:sync-config` an). Spielerdaten werden nicht angefasst.
- Die Referenzdaten stehen in `database/seeders/data/*.php`; Werteänderungen kommen über Config bzw. diese Dateien und wirken beim nächsten Deploy.

## Nie in Produktion

- **Nie `DatabaseSeeder` oder `TestSeeder`:** Sie legen Testspieler und Fixtures an. `DatabaseSeeder` verweigert Produktion bereits.
- Keine Dev-Kommandos (`db:reset`, `game:reset-player`, `colony:seed-demo`, `game:playtest`): Sie brechen in Produktion ab.
- Keine Dev-/Testdaten, keine Test-Accounts: Die Produktions-DB ist frisch.

## Admin-Account

Der Owner-Account mit `role = admin` wird einmalig per Kommando angelegt (Commands-Tab der Umgebung, siehe ADR 0005); das Anlege-Kommando ist Teil von R4. Passwort-Reset im Notfall: `php artisan user:reset-password <username|email>`.

## Schema-Änderungen nach dem Produktivgang

Sobald R4 live ist, gilt: **Die Baseline wird nie mehr editiert.** Jede Schema-Änderung kommt als neue Migration mit eigenem Zeitstempel, damit `migrate --force` sie auf der bestehenden DB nachzieht. Vor dem Produktivgang wurde die Baseline noch direkt angepasst; das erzwingt lokal einen frischen Aufbau (`php artisan db:reset --force`), in Produktion wäre es ein Datenverlust.

**View `v_glx_colonies`:** MySQL friert `SELECT *` beim Anlegen zu einer festen Spaltenliste ein. Nach jeder Änderung an `glx_colonies` muss die View per neuer Migration neu angelegt werden (`DROP VIEW` + `CREATE SQL SECURITY INVOKER VIEW … AS SELECT * FROM glx_colonies`), sonst sieht sie neue Spalten nie; `INVOKER` verhindert Fehler 1449 nach einem Restore unter anderem DB-User.

Referenzdaten sind keine Migrationen: Neue oder geänderte Stammdaten gehören in `database/seeders/data/<tabelle>.php`. Der Seeder löscht keine Zeilen, die aus den Datendateien entfernt werden; Entfernen braucht eine eigene Migration. Grenze des Upserts: Auf MySQL wird er zu `ON DUPLICATE KEY UPDATE` und greift bei jedem Unique-Key (Namens-Indizes, Techtree-Positionen), nicht nur bei der angegebenen ID; Umbenennungen und Positionstausch brauchen deshalb eine eigene Migration.

## Backup und Restore

Tägliche Snapshots der Laravel-MySQL-Instanz, Restore nur in einen neuen Cluster (Umschalten per Deploy), bis zu 24 h Datenverlust akzeptiert. Einrichtung, Aufbewahrung, Probe-Restore und Export-Weg sind in R6 (ROADMAP) und im ADR 0005 beschrieben.
