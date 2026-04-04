## Change: Moralsystem — Initiales Design (Phase 2)

**Datum:** 2026-04-03

### Values before

Kein Moralsystem. `colony_resources.amount` (resource_id=12) existiert in der DB mit Startwert 0, hatte aber keine Spielwirkung.

### Values after

**Wertebereich:** -100 bis +100 (Integer, clamp-gesichert), Neutral = 0

**Gebäude-Moralwerte (moral/Level):**

| building_id | Bezeichner | Wert |
|-------------|------------|------|
| 32 | temple | +2 |
| 45 | parc | +2 |
| 46 | hospital | +3 |
| 48 | public_security | +1 |
| 50 | denkmal | +2 |
| 51 | university | +2 |
| 53 | stadium | +3 |
| 56 | museum | +2 |
| 65 | recyclingStation | +1 |
| 52 | bar | -1 |
| 54 | casino | -2 |
| 55 | prison | -3 |
| 64 | wastedisposal | -1 |
| 66 | secretOps | -2 |
| 68 | militarySpaceyard | -1 |

**Forschungs-Moralwerte (moral/Level):**

| research_id | Bezeichner | Wert |
|-------------|------------|------|
| 33 | biology | +1 |
| 72 | medicalScience | +2 |
| 79 | diplomacy | +1 |
| 80 | politicalScience | +1 |
| 81 | military | -1 |
| 34 | languages | +1 |

**Produktions-Multiplikatoren:**

| Moral-Band | Multiplikator |
|------------|---------------|
| +61 bis +100 | 1.20 |
| +21 bis +60 | 1.10 |
| -20 bis +20 | 1.00 |
| -60 bis -21 | 0.85 |
| -100 bis -61 | 0.70 |

**AP-Multiplikatoren:**

| Moral-Band | Multiplikator |
|------------|---------------|
| +61 bis +100 | 1.10 |
| +21 bis +60 | 1.05 |
| -20 bis +20 | 1.00 |
| -60 bis -21 | 0.90 |
| -100 bis -61 | 0.80 |

**Event-Effekte (einmalig, 1 Tick):**

| Event | Moraleffekt |
|-------|-------------|
| trade_success | +2 |
| building_level_up | +1 |
| building_level_down | -3 |
| combat_won | +2 |
| combat_lost | -5 |
| research_level_up | +2 |
| treaty_signed | +3 |
| war_declared | -8 |

### Rationale

Moral soll die zivil-orientierte Spielweise belohnen ohne Militaristen zu blockieren. Die Multiplikatoren-Struktur (statt additiver Boni) verhindert, dass Moral als pure Produktionsstrategie optimiert wird. Die Wahl des Banden-Modells (5 Stufen statt linearer Funktion) ist implementierungseinfacher und erzeugt klare Schwellenwerte, die Spieler verstehen und ansteuern können.

### Expected effect

- Spieler mit reiner Zivilkolonie (temple, hospital, stadium, museum, parc) erreichen nach einigen Ticks Moralband "Zufrieden" bis "Euphorisch" und erhalten +10–20% Produktion.
- Spieler mit hohem Militärfokus (mehrere Militärwerften, kein Zivilbau) landen im negativen Band und erhalten -15% Produktion und -10% AP.
- Reine Prison/Casino-Spam-Strategien werden durch Moralmalus bestraft.
- Der AP-Malus bei Krise (-20%) ist selbstverstärkend, kann aber durch gezielten Zivilbau durchbrochen werden.

### Offene Balance-Fragen (nach erstem Playtest zu evaluieren)

1. Sind die Gebäude-Moralwerte zu groß? Ein Spieler mit hospital Lv10 + stadium Lv10 + temple Lv10 allein = 80 Punkte (fast Deckel). Eventuell Werte halbieren: hospital +1.5/Level etc.
2. Reicht der Event-Bonus von +1 bei building_level_up als Belohnung fur aktives Spielen, oder sollte es +3 sein?
3. Sollte der AP-Malus bei Krise noch tiefer gehen (-30% bei Aufruhr) oder ist -20% bereits stark genug für Abschreckung?
