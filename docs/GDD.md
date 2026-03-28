# Nouron — Game Design Document (GDD)

**Projekt:** Nouron — A Free Space Opera Browsergame
**Status:** aktiv (Stand: 2026)
**Verantwortlich:** Mario Gehnke

---

## Inhaltsverzeichnis

1. [Spielkonzept](#1-spielkonzept)
   - 1.1 [Designprinzipien](#11-designprinzipien)
2. [Tick-System](#2-tick-system)
3. [Ressourcen](#3-ressourcen)
4. [Kolonien & Gebäude](#4-kolonien--gebäude)
5. [Ressourcenproduktion](#5-ressourcenproduktion)
6. [Supply-Generierung](#6-supply-generierung)
7. [Gebäude-Verfall (Decay)](#7-gebäude-verfall-decay)
8. [Flotten & Flottenorders](#8-flotten--flottenorders)
9. [Kampfsystem (Combat)](#9-kampfsystem-combat)
10. [Forschung](#10-forschung)
11. [Handel (Trade)](#11-handel-trade)
12. [Berater & Aktionspunkte (AP-System)](#12-berater--aktionspunkte-ap-system)

---

## 1. Spielkonzept

Nouron ist ein rundenbasiertes Weltraum-Strategiespiel im Browser. Spieler bauen Kolonien auf, erforschen Technologien, bauen Flotten und interagieren mit anderen Spielern durch Handel und Kampf. Das Spiel läuft servergesteuert auf Basis eines Tick-Systems: alle Spielzustandsänderungen werden einmal pro Tag global berechnet.

---

## 1.1 Designprinzipien

### Militarismus ist teuer — bewusst

**Kernprinzip: Militarische Aktionen kosten immer mehr AP als nicht-militarische.**

Dies ist kein technisches Detail, sondern eine fundamentale Designentscheidung. Nouron ist kein Kriegsspiel. Die Kernphantasie ist der Aufbau eines florierenden Imperiums durch Handel, Infrastruktur und Forschung. Militarische Macht ist ein Mittel zur Absicherung, nicht das Ziel.

### Warum dieses Prinzip

Ohne Kostenasymmetrie dominiert in Strategie-Browserspielen erfahrungsgemass eine einzelne optimale Strategie: maximale Militarisierung, da Angriff die billigste Form der Ressourcengewinnung ist. Das zerstoert den Spielspass fur alle, die eine andere Spielweise bevorzugen.

Indem militarische Aktionen strukturell teurer sind, wird Militarismus nicht verboten, aber er hat einen realen Opportunitatskostenfaktor. Ein Spieler, der standig angreift, hat weniger AP fur Aufbau, Forschung und Handel — und wachst dadurch langsamer als ein Spieler, der sich auf friedliche Entwicklung konzentriert.

### Umsetzung im AP-System

Navigation-AP werden durch **Piloten** generiert und decken alle Flottenorders ab — zivile wie militarische. Die Differenzierung erfolgt ausschliesslich uber die AP-Kosten je Order-Typ:

| Order-Typ | Navigation-AP-Kosten |
|-----------|----------------------|
| move (Bewegung) | 1 |
| trade (Handel) | 1 |
| colonize (Kolonisierung) | 2 |
| attack (Angriff) | 3 |

Ein Pilot, der 15 AP pro Tick generiert, kann also entweder:
- 15 Bewegungs- oder Handels-Orders erteilen, oder
- 7 Kolonisierungs-Orders, oder
- 5 Angriffs-Orders

Die zivile Variante erzeugt dreimal so viele Aktionen wie die militarische bei gleicher AP-Basis.

### Geltungsbereich: spielweites Prinzip

Diese Asymmetrie gilt nicht nur fur Flottenorders. Sie muss in allen zukunftigen Mechaniken, die AP-Kosten oder andere Kosten haben, konsequent angewendet werden:

- **Diplomatie / Vertrage**: Angriffs- oder Sanktionsvertrage kosten mehr als Handels- oder Beistandsvertrage.
- **Politiksystem**: Kriegserklarungen und Embargos verbrauchen mehr politische AP als Allianzen oder Handelsabkommen.
- **Spionage / Geheimdienstoperationen**: Sabotageaktionen kosten mehr als Informationssammlung.
- **Neue Schiffstypen**: Kampfschiffe sind teurer in Bau-AP als zivile Schiffe vergleichbarer Grosse.

> Jede neue Spielmechanik muss beim Design gepruft werden: Ist die militarische Variante teurer als die zivile? Wenn nicht, ist die Mechanik nicht balanciert im Sinne der Nouron-Vision.

### Abgrenzung

Das Prinzip bedeutet nicht, dass Militarismus unmoglich oder unrentabel ist. Ein hochspezialisierter Militarspieler, der alle Piloten auf maximales Level bringt, kann trotzdem erhebliche Kampfkapazitat aufbauen. Die Kostenadditionalitat stellt sicher, dass diese Spezialisierung eine echte Wahl ist — mit echten Opportunitatskosten — und nicht die einzig sinnvolle Strategie.

---

## 2. Tick-System

### Grundprinzip

Ein **Tick** entspricht einem Spieltag. Alle periodischen Spielmechaniken (Ressourcenproduktion, Gebäudeverfall, Flottenorders) werden einmal pro Tick ausgeführt.

### Zeitberechnung

Die Tick-Nummer ergibt sich aus:

```
tick = floor((unix_timestamp - 4h) / 86400)
```

Dies entspricht der Anzahl vergangener Tage seit der Unix-Epoch, verschoben um 4 Stunden (damit der Tagesübergang um Mitternacht nicht mit dem Berechnungsfenster kollidiert).

### Berechnungsfenster

Der Tick wird täglich automatisch zwischen **03:00 und 04:00 Uhr Serverzeit** berechnet (konfigurierbar in `config/game.php → tick.calculation`).

### Manueller Aufruf

```bash
php artisan game:tick           # berechnet den aktuellen Tick
php artisan game:tick --tick=N  # erzwingt Tick-Nummer N (z. B. für Tests)
```

### Implementierung

- Artisan-Command: `app/Console/Commands/GameTick.php`
- Tick-Berechnung: `app/Services/TickService.php`
- Konfiguration: `config/game.php → tick`
- Alle Schritte eines Ticks laufen in einer einzigen DB-Transaktion (atomar)

### Reihenfolge der Tick-Schritte

| Schritt | Beschreibung |
|---------|-------------|
| 1 | Fleet Move Orders — Flotten bewegen sich |
| 2 | Fleet Trade Orders — Ressourcentransfer Flotte ↔ Kolonie |
| 3 | Fleet Combat Orders — Kampfauflösung |
| 4 | Building Decay — Gebäude verlieren Status-Punkte |
| 5 | Supply Generation — Supply für alle User berechnen |
| 6 | Resource Generation — Rohstoffproduktion pro Kolonie |

---

## 3. Ressourcen

9 Ressourcentypen, nicht-konsekutive IDs:

| ID | Name | Kürzel | Ebene | Handelbar | Startwert |
|----|------|--------|-------|-----------|-----------|
| 1  | Credits | Cr | User | Nein | 3000 |
| 2  | Supply | Sup | User | Nein | 200 |
| 3  | Wasser | W | Kolonie | Ja | 500 |
| 4  | Ferum | E | Kolonie | Ja | 500 |
| 5  | Silikate | S | Kolonie | Ja | 500 |
| 6  | Ena (elektr. Energie) | ENrg | Kolonie | Ja | 100 |
| 8  | Lho (leichte Energie) | LNrg | Kolonie | Ja | 100 |
| 10 | Aku (Akkuenergie) | ANrg | Kolonie | Ja | 100 |
| 12 | Moral | M | Kolonie | Nein | 0 |

**Credits** und **Supply** werden auf User-Ebene (`user_resources`) geführt, alle anderen auf Kolonieebene (`colony_resources`).

---

## 4. Kolonien & Gebäude

### Gebäude (Auswahl)

| ID | Bezeichner | Max-Level | Voraussetzung |
|----|-----------|-----------|---------------|
| 25 | CommandCenter | 10 | — |
| 27 | Erzmine (oremine) | — | CC Lv1 |
| 28 | Wohnkomplex (housingComplex) | 200 | CC Lv3 |
| 31 | Forschungslabor (sciencelab) | — | CC Lv4 |
| 41 | Silikatmine (silicatemine) | — | CC Lv1 |
| 42 | Wasserextraktor (waterextractor) | — | CC Lv1 |
| 43 | Handelszentrum (tradecenter) | — | CC Lv5 |
| 44 | Zivile Werft (civilianSpaceyard) | — | — |
| 68 | Militärwerft (militarySpaceyard) | — | Zivile Werft Lv5 |
| 70 | Bank | — | — |

Vollständige Liste: CLAUDE.md, Abschnitt "Gebäude".

### Status-Punkte

Jedes Koloniegebäude hat ein `status_points`-Feld. Das Maximum (`max_status_points`) ist in der `buildings`-Tabelle hinterlegt. Status-Punkte sinken pro Tick durch Verfall (siehe Abschnitt 7).

---

## 5. Ressourcenproduktion

### Mechanik

Einmal pro Tick produziert jedes aktive Produktionsgebäude in jeder Kolonie Rohstoffe. Die produzierte Menge ergibt sich aus:

```
produzierte Menge = Gebäude-Level × Rate
```

### Produktionsgebäude (aktuell konfiguriert)

| Gebäude | building_id | Ressource | resource_id | Rate pro Level |
|---------|-------------|-----------|-------------|----------------|
| Erzmine | 27 | Ferum | 4 | 10 |
| Silikatmine | 41 | Silikate | 5 | 10 |
| Wasserextraktor | 42 | Wasser | 3 | 10 |

### Konfiguration

`config/game.php → production`:

```php
'production' => [
    27 => [4 => 10],   // oremine        → ferum      × 10/level
    41 => [5 => 10],   // silicatemine   → silicates  × 10/level
    42 => [3 => 10],   // waterextractor → water      × 10/level
],
```

Neue Produktionsgebäude können ohne Code-Änderung ausschließlich durch Erweiterung dieser Config hinzugefügt werden.

---

## 6. Supply-Generierung

### Mechanik

Supply ist eine User-Level-Ressource und repräsentiert die Versorgungskapazität des Imperiums. Sie steigt einmal pro Tick basierend auf den gebauten Infrastrukturgebäuden über alle Kolonien des Spielers.

```
Supply-Zuwachs = Σ(CC-Level × CC-Rate) + Σ(Wohnkomplex-Level × Wohnkomplex-Rate)
```

(Summe über alle Kolonien des Users)

### Konfigurierte Raten

| Gebäude | building_id | Rate pro Level |
|---------|-------------|----------------|
| CommandCenter | 25 | 5 Supply/Tick |
| Wohnkomplex | 28 | 10 Supply/Tick |

### Konfiguration

`config/game.php → supply`:

```php
'supply' => [
    'commandcenter_rate'  => 5,
    'housingcomplex_rate' => 10,
],
```

---

## 7. Gebäude-Verfall (Decay)

### Mechanik

Gebäude verfallen ohne aktive Pflege. Einmal pro Tick verliert jedes aktive Koloniegebäude (Level > 0) einen festgelegten Betrag an `status_points`.

Erreichen die `status_points` den Wert 0 oder darunter:
1. Das Gebäude verliert **ein Level** (`level -= 1`, Minimum: 0)
2. Die `status_points` werden auf `max_status_points` zurückgesetzt (Wert aus `buildings`-Tabelle)
3. Ein INNN-Ereignis `techtree.level_down` wird für den Koloniebesitzer erzeugt

### Designabsicht

Decay erzwingt, dass Spieler regelmäßig Aktionspunkte in Reparaturen investieren, um ihren Gebäudestand zu erhalten. Inaktive Spieler verlieren schrittweise ihre Infrastruktur.

### Konfiguration

`config/game.php → decay`:

```php
'decay' => [
    'rate' => 1,   // status_points-Verlust pro Tick
],
```

---

## 8. Flotten & Flottenorders

### Flottenorders

Flottenbewegungen und -aktionen werden als Orders in der `fleet_orders`-Tabelle gespeichert. Jede Order ist einem Tick zugewiesen und wird beim zugehörigen Tick genau einmal verarbeitet (`was_processed = 1` nach Ausführung).

### Navigation-AP-Kosten je Order-Typ

Jede Flottenorder verbraucht Navigation-AP, die durch Piloten generiert werden (siehe Abschnitt 12). Die AP-Kosten unterscheiden sich bewusst je nach Charakter der Aktion — militarische Orders sind teurer (siehe Abschnitt 1.1, Designprinzip "Militarismus ist teuer").

| Order-Typ | Navigation-AP-Kosten | Kategorie |
|-----------|----------------------|-----------|
| move | 1 | zivil |
| trade | 1 | zivil |
| colonize | 2 | zivil |
| attack | 3 | militarisch |

> Die Kostenwerte sind in `config/game.php → fleet.order_costs` konfiguriert. Neue Order-Typen muessen beim Anlegen immer einen Eintrag dort erhalten. Das Verhaltnisprinzip (militarisch >= zivil) darf dabei nicht verletzt werden.

### Move-Order

Bewegt eine Flotte zu Zielkoordinaten `[x, y, spot]`.

- Nach Ausführung wird die Position der Flotte (`fleets.x`, `fleets.y`, `fleets.spot`) aktualisiert
- INNN-Ereignis `galaxy.fleet_arrived` wird für den Flottenbesitzer erzeugt

### Trade-Order

Transferiert Ressourcen zwischen einer Kolonie und einer Flotte.

| direction | Bedeutung |
|-----------|-----------|
| 0 | Kauf: Kolonie gibt Ressource an Flotte |
| 1 | Verkauf: Flotte gibt Ressource an Kolonie |

- Koloniebestand kann nicht unter 0 sinken (Schutz via `MAX(0, amount - amount)`)
- Flottenbestand kann nicht unter 0 sinken
- INNN-Ereignis `galaxy.trade` wird für den Flottenbesitzer erzeugt

---

## 9. Kampfsystem (Combat)

### Ablauf

Eine Attack-Order löst folgende Schritte aus:

1. Der Angreifer bewegt sich zu den Zielkoordinaten
2. Alle fremden Flotten an diesen Koordinaten werden als Verteidiger identifiziert
3. Kampfstärken werden berechnet
4. Verluste werden anteilig verteilt
5. INNN-Ereignis `galaxy.combat` wird für beide Seiten erzeugt

### Kampfstärke

```
Kampfstärke einer Flotte = Σ(Schiffanzahl × Kampfwert des Schiffstyps)
```

### Kampfwerte der Schiffstypen

| Schiff | ship_id | Kampfwert |
|--------|---------|-----------|
| Fighter 1 | 37 | 1 |
| Fregatte 1 | 29 | 3 |
| Schlachtkreuzer 1 | 49 | 10 |
| Kleiner Transporter | 47 | 0 |
| Mittlerer Transporter | 83 | 0 |
| Großer Transporter | 84 | 0 |
| Kolonisationsschiff | 88 | 0 |

Schiffe mit Kampfwert 0 sind **nicht-kampffähig** und werden im Gefecht nicht zerstört.

### Verlustberechnung

```
Verlustquote Angreifer = Verteidiger-Stärke / Gesamtstärke
Verlustquote Verteidiger = Angreifer-Stärke / Gesamtstärke
Gesamtstärke = Angreifer-Stärke + Verteidiger-Stärke
```

Verluste je Schiffstyp:

```
Verluste = ceil(Anzahl × Verlustquote)
```

Sinkt eine Schiffsklasse auf 0 oder darunter, wird der Eintrag aus `fleet_ships` gelöscht.

Haben beide Seiten keine kampffähigen Schiffe (Gesamtstärke = 0), findet kein Kampf statt.

### Konfiguration

`config/game.php → combat.ship_power`:

```php
'combat' => [
    'ship_power' => [
        37 => 1,   // fighter1
        29 => 3,   // frigate1
        49 => 10,  // battlecruiser1
        47 => 0,   // smallTransporter
        83 => 0,   // mediumTransporter
        84 => 0,   // largeTransporter
        88 => 0,   // colonyShip
    ],
],
```

Neue Schiffstypen und deren Kampfwerte werden ausschließlich in dieser Config konfiguriert.

---

## 10. Forschung

10 Forschungsgebiete: biology, languages, mathematics, medicalScience, physics, chemistry, economicScience, diplomacy, politicalScience, military.

Forschung wird über Aktionspunkte (AP) vorangetrieben. Details zum AP-System sind im Techtree-Modul implementiert (`module/Techtree/`, in Laravel: `app/Services/`).

*Spielmechanik noch nicht vollständig dokumentiert — wird in Phase 2 ergänzt.*

---

## 11. Handel (Trade)

Ressourcen und Forschungen können über Handelsrouten zwischen Spielern transferiert werden. Angebote werden in `trade_resources` / `trade_researches` gespeichert.

| direction | Bedeutung |
|-----------|-----------|
| 0 | Kaufangebot (Spieler will kaufen) |
| 1 | Verkaufsangebot (Spieler will verkaufen) |

Handelsrouten werden über Flottenorders (`order = 'trade'`) abgewickelt.

*Vollständige Mechanik noch nicht dokumentiert — wird in Phase 2 ergänzt.*

---

---

## 12. Berater & Aktionspunkte (AP-System)

### Grundkonzept

Aktionspunkte (AP) sind die zentrale Handlungswährung in Nouron. Sie begrenzen, wie viel ein Spieler pro Tick in Gebäude, Forschung, Flotten und Handel investieren kann. AP werden durch **Berater** generiert.

Berater sind **individuelle Einträge mit Rang** — jeder Berater hat eine eigene ID und einen Zustand. Eine Kolonie kann mehrere Berater desselben Typs beschäftigen; jeder gibt seinen AP-Beitrag pro Tick. Qualitätsunterschiede entstehen durch das Rang-System.

> **Phase-3-Vorbehalt:** Benannte Chef-Berater (individuelle Charaktere mit Fähigkeiten und Namen) sind für Phase 3 geplant. Das aktuelle individuelle Eintrags-Modell ist bewusst als Fundament dafür ausgelegt.

---

### Datenmodell: `advisors`-Tabelle

```
advisors
├── id                      ← eindeutige ID des Beraters
├── user_id                 ← Eigentümer (immer gesetzt)
├── personell_id            ← FK → personell (Typ)
├── colony_id               ← nullable: aktiv auf dieser Kolonie
├── fleet_id                ← nullable: auf dieser Flotte
├── is_commander            ← boolean: führt die Flotte (nur Kommandant-Typ)
├── rank                    ← 1/2/3 (Junior/Senior/Experte)
├── active_ticks            ← für Rang-Aufstieg gezählt
└── unavailable_until_tick  ← Erholung nach Kampfverlust (NULL = verfügbar)

CHECK: colony_id IS NULL OR fleet_id IS NULL
```

**Mögliche Zustände eines Beraters:**

| colony_id | fleet_id | is_commander | Bedeutung | Gilt für |
|-----------|----------|--------------|-----------|----------|
| gesetzt | NULL | false | Aktiv auf Kolonie, generiert AP | Alle Typen |
| NULL | gesetzt | **true** | Führt Flotte, generiert Nav-AP | **Nur Kommandant** |
| NULL | gesetzt | false | Passagier auf Flotte (Transport) | Alle Typen |
| NULL | NULL | false | Arbeitslos — handelbar, re-assignierbar | Alle Typen |

**Validierungsregel:** `is_commander = true` ist nur erlaubt wenn `personell.can_command_fleet = true`. Das Flag `can_command_fleet` steht in der `personell`-Mastertabelle und ist nur für den Kommandant-Typ gesetzt.

**Entlassung** löscht keinen Berater — `colony_id` und `fleet_id` werden auf NULL gesetzt. Der Berater bleibt arbeitslos in der Tabelle und kann re-aktiviert oder an andere Spieler gehandelt werden.

---

### Die vier Berater-Typen

| Berater | Personell-ID | Kategorie | AP-Typ | Verwendung |
|---------|-------------|-----------|---------|------------|
| Ingenieur | 35 | industry | construction | Gebäude ausbauen, Gebäude reparieren, Schiffsbau |
| Wissenschaftler | 36 | civil | research | Forschungen vorantreiben |
| Kommandant | 89 | military | navigation | Flottenorders (bewegen, angreifen, handeln) |
| Händler | 92 | economy | economy | Handelsangebote erstellen und pflegen |

> **Umbenennung:** "Pilot" heißt in der UI jetzt **Kommandant** (DB-Name `techs_pilot` bleibt intern erhalten).

---

### Rang-System

Jeder Berater hat einen Rang (1–3), der seine AP-Leistung bestimmt.

| Rang | Bezeichnung | AP/Tick | Anwerbungskosten | Aufstieg |
|------|-------------|---------|-----------------|----------|
| 1 | Junior | 4 AP | 200 Cr | — |
| 2 | Senior | 7 AP | +500 Cr | 10 Ticks aktiv |
| 3 | Experte | 12 AP | +1000 Cr | 20 Ticks aktiv |

- Aufstieg erfolgt automatisch nach ausreichend aktiven Ticks.
- Optional: Aufstieg per Credits-Zahlung beschleunigen (halbe Zeit, doppelter Preis).
- "Aktiv" = Berater war in diesem Tick beschäftigt und hat AP generiert.
- Die konkreten Werte (AP, Kosten, Ticks) sind konfigurierbar und werden nach erstem Playtest angepasst.

---

### Supply-Kosten (VP — Versorgungspunkte)

Supply ist die universelle Unterhaltsressource. **Alles kostet dauerhaft Supply:**

| Was | Supply/Tick |
|-----|-------------|
| Berater (je) | 2 Supply |
| Gebäude (je Level) | *noch zu definieren* |
| Schiffe (je Einheit) | *noch zu definieren* |

Supply wird durch **Koloniezentrum** und **Wohnkomplex** generiert. Das Hard-Cap liegt bei **200 Supply pro Kolonie**. Da pro Spieler nur eine Kolonie vorgesehen ist (Phase 3), ist Supply der einzige und ausreichende Kapazitätsdeckel für Berater.

**Flotten und Supply:** Schiffe verbrauchen Supply über ihre **Heimatkolonie** — eine Flotte ist immer einem Heimathafen zugeordnet, der die laufenden Kosten trägt. *Details der Heimathafen-Mechanik werden in Phase 2 ausgearbeitet.*

---

### Kommandant: Kolonie vs. Flotte

Der Kommandant ist der einzige Beratertyp, der seinen Koloniebezug verlieren kann:

- **Kolonie-zugewiesen:** Gibt der Kolonie Navigation-AP (für das Erteilen neuer Flottenorders).
- **Flotten-zugewiesen:** Gibt der Flotte direkt Navigation-AP; Koloniebezug aufgehoben.
- **Rückkehr:** Beim Anlegen an der Heimatkolonie wird der Kommandant automatisch wieder der Kolonie zugewiesen.
- **Flottenverlust im Kampf:** Der Kommandant ist für 2–3 Ticks nicht verfügbar (erholt sich), geht aber nicht dauerhaft verloren.

---

### Verfügbare AP

```
availableAP = Σ(AP/Tick je Berater) − lockedAP(tick, scope_type, scope_id, personell_id)
```

`scope_type = 'colony'` für Ingenieur/Wissenschaftler/Händler, `scope_type = 'fleet'` für Kommandant.

AP-Locks verfallen automatisch zum nächsten Tick — der Pool wird täglich vollständig erneuert.

### AP-Verbrauch

1. **Bauen/Forschen/Handel:** AP werden beim Investieren gesperrt (`invest('add')`).
2. **Reparatur/Abbau:** AP werden in Höhe der veränderten `status_points` gesperrt.
3. **Flottenorder:** AP-Kosten abhängig von Order-Typ (siehe §1.1 und §8).

### Implementierung

- `app/Services/Techtree/PersonellService.php` — AP-Berechnung, Sperrung
- `app/Services/Techtree/AbstractTechnologyService.php` — AP-Verbrauch beim Investieren
- `app/Services/FleetService.php` — Navigation-AP-Check bei Order-Erstellung
- Tabelle `locked_actionpoints`: `(tick, scope_type, scope_id, personell_id, spend_ap)`

### Dev-Mode

Im Dev-Mode (`GAME_DEV_MODE=true` in `.env`, Standard) werden Ressourcen- und AP-Kosten übersprungen. Das AP-System selbst bleibt aktiv für Tests.

---

*Dokument erstellt: 2026-03-26. Weitere Abschnitte werden im Verlauf von Phase 2 ergänzt.*
