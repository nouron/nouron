# Regolith-Wirtschaft: Budget-Modell „grüner Rasen" (Entwurf)

> **Owner-Entscheide 2026-10-14 (nach Entwurf, haben Vorrang vor dem Text unten):**
> 1. Hangar-/Cantina-Rg: im Schnitt planbar, pro Aktion zufällig (kehrt die Linie vom 2026-10-09 um).
> 2. Wertepaket (Harvester 15/21/27, Start-Rg 260, Geologie +6) ist Zielbild; Zahlen erst nach Ledger + Basislauf festlegen (Lücke Bot ~17 vs. Referenz ~30 Rg/Sol klären; Referenzspieler ist Rechenmodell, kein Messwert).
> 3. Schiffe kosten kein Regolith (nur Credits; Lore: Kolonie kann keine Schiffe bauen).
> 4. Zweiter Harvester entfällt komplett (Idee geparkt); Ruine `event_ruin`/Missionen `mission_harvester_salvage`, `mission_ruin_expedition` streichen oder parken.
> 5. Berater geben AP-Boni, keinen Rg-Bonus; Kenntnisse erhöhen Rg-Ertrag, über die drei Pfade ausbalanciert.
> 6. Grund-AP senken / Berater-AP erhöhen: nur als Rechenparameter parken (milde Variante 10 / Ränge 2/4/5).
> 7. Bau-Gate „Pfadgebäude < CC-Level − 1" wird Server-Regel; Berater-Rang anheuern: Rang 3 nicht kaufbar (Siegziel „Kernstab", eigener GDD-Entwurf später).


**Stand:** 2026-10-14 · **Autor:** game-designer (Entwurf, keine Code-/Config-Änderung) · **Grundlage:** `2026-10-14-regolith-wirtschaft-inventar.md` (Inventar, im Folgenden „Inv."), `2026-10-13-bergung-ohne-sol-deckel.md` (Bergung), `2026-10-05-t30-gleichwertige-eroeffnungen.md` (T30), `docs/game-reference.md`, Config-Stand Commit dd9a4389.

**ADR 0004:** Alle Zahlen in diesem Dokument sind **Vorschlag bzw. Kalibrierungsstart**. Die Config bleibt Quelle der Wahrheit; das künftige Rechenmodell (`tools/economy-model.php`, Abschnitt E.3) rechnet aus der Config, nicht aus diesem Dokument. Das GDD erhält nur die zahlenfreien Prinzipien aus Abschnitt 0.

**Kennzeichnung:** **Config** = heute gelesen · **Messwert** = aus CHANGELOG/Specs · **Vorschlag** = neu · **Annahme** = eigene Deutung oder Handrechnung.

---

## 0. Kurzfassung und Leitprinzipien

**Owner-Vorgaben (verbindlich, Auftrag 2026-10-14):**
1. AP bremst im Normalfall. Regolith ist nur gelegentlich Engpass: 1–2 Großanschaffungen je Phase (CC-Stufe, Frachter) erzwingen kurzes Sparen. Messbar: wenige `resource_limit`-Abweisungen, Rg-Bestand nie dauerhaft nahe 0.
2. Budget-Modell von null; alle Quellen und Senken berücksichtigen.
3. Eichung über AP: Bau-AP je Phase × Rg-Preis je AP ergibt den Rg-Bedarf; er soll 85–95 % des Rg-Einkommens im Zeitraum betragen. Startbestand und Rücklage decken die Großkäufe. Das Rechenmodell ist die Quelle der Wahrheit. Einkommen: Harvester ca. 60–70 % (Grundlast), Missionen/Funde/Cantina ca. 30–40 %. Alle drei Eröffnungen haben eigenes Rg-Einkommen.
4. Kenntnisse erhöhen den Rg-Ertrag (je Pfad eine) und sind über die drei Pfade ausbalanciert. Berater geben **keinen** Rg-Bonus, sondern AP-Boni. Kein Rabatt auf Rg-Kosten (Rg-Kosten fix).

**Leitprinzipien des Modells (Vorschlag, zahlenfrei, GDD-tauglich):**

| # | Prinzip | Wirkung |
|---|---|---|
| P1 | **Rg hat einen festen Preis je Bau-AP.** Eine Ausbaustufe kostet Rg im festen Verhältnis zu ihren Basis-AP. Neubau und CC-Stufe kosten ein Vielfaches davon (Großanschaffung). | „Breite kostet Regolith, Tiefe kostet AP" bleibt; der Rg-Bedarf folgt dem AP-Einsatz. |
| P2 | **Harvester = AP-freie Grundlast**, rund zwei Drittel des Einkommens. | Planbarer Sockel, Kartenbudget bleibt die harte Obergrenze. |
| P3 | **Pfad-Rg kostet AP** (Labor: einmalig als Forschung; Hangar und Cantina: laufend je Aktion). Der Kurs liegt in der Größenordnung des Bau-Preises. | Wer mehr bauen will, muss AP in Erwerb statt in Forschung stecken. Genau das macht AP zum Engpass. |
| P4 | **Pfad-Rg ist im Mittel kalkulierbar, im Einzelfall zufällig** (Wurf, Ertragsspanne, Angebotstakt). | Erwartungswert ist eichbar, das Spielgefühl bleibt „unregelmäßig" (Owner-Linie 2026-10-09, siehe Frage G.1). |
| P5 | **Je Pfad eine Kenntnis hebt den Pfad-Ertrag** (Geologie, Kartografie, Handel), geeicht auf gleiche Rg-Wirkung je Stufe bei Referenznutzung. | Pfadparität über Kenntnisse statt über Sonderregeln. |
| P6 | **Rabatte senken nur AP.** Weil Rg fix ist, steigt mit wachsender Kolonie (Bauwesen, Berater-Rang) der effektive Rg-Preis je ausgegebenem Bau-AP. | Natürliches Gegengewicht zum wachsenden Einkommen in Phase 2. |
| P7 | **Uplink-Import ist Preisdeckel**, kein Normalweg. Jede Rg-Quelle im Spiel ist im Credits-Gegenwert günstiger als der Import. | Credits-Reichtum kann Rg-Knappheit lösen, aber nur teuer und verzögert. |

**Kernergebnis der Handrechnung (Annahme, Abschnitt C.4):** Mit den Vorschlagswerten liegt die Rg-Ausschöpfung in Phase 2 für alle drei Eröffnungen bei 83–94 %, der Harvester-Anteil bei 59–72 %, der Rg-Bestand zwischen Sol 16 und 60 bei rund 100–270 (Tiefpunkte direkt nach CC-Stufe, Frachter oder drittem Pfadgebäude). Phase 1 bleibt bewusst Rg-getaktet (Ideal Sol 12–13, Bot erwartet 16–18).

---

## 1. Rahmen und Annahmen

| # | Annahme | Begründung |
|---|---|---|
| R1 | **Referenzspieler = „guter Spieler"**: kauft, sobald Rg und AP reichen, verschwendet keine AP. Phasenende Ideal ca. Sol 12–13, realistisch 14–15, Bot 16–18. | T30 §1.4 (Ideal 12–13 ↔ Bot 17–18 bei gleichem Kurs); Owner: Menschen sollen 70–80 % gewinnen, Bot ca. 50 %. |
| R2 | **Referenzkachel y2** (mittlere Ertragsstufe). y1/y3 als Empfindlichkeit (C.5). | wie T30 |
| R3 | **Phasenfenster:** P1 = Sol 1–15, P2a = Sol 16–40 (Ausbau: CC 4, drittes Pfadgebäude, Frachter, Uplink), P2b = Sol 41–60 (CC 5, Vollausbau), P3 = Sol 61–90 (Siegziele, Erhalt). | Phase-1-Korridor 15–20, Sieg 70–95 |
| R4 | **Vertrauen:** Produktions-Multiplikator 1,00 in P1, im Mittel 1,04 in P2a, 1,10 in P2b/P3. AP-Multiplikator 1,05 ab ca. Sol 26. | Bänder `game.trust.*`; Vertrauen 21–60 ab Mitte Phase 2 (Annahme) |
| R5 | **Harvester-Laufzeit:** 4 % Ausfall durch Verlegung und Instabilität ab P2a. | Verlegung alle ca. 20 Sole, 1 Sol ohne Ertrag (Annahme) |
| R6 | **Pfad-Auslastung:** Schiffe und Cantina-Gäste werden nicht vollständig für Rg genutzt, weil Credits-Missionen, Siegziel-Expeditionen und Credits-Handel konkurrieren. Hangar 75 % (P2a) bzw. 55 % (ab P2b), Cantina 80 %. In Phase 1 (Ideal) 100 %. | Annahme, im Modell als Parameter; Messung K29 |
| R7 | **Drittes Pfadgebäude erst ab CC 4** (ca. Sol 27–34). | T30 §10.2 nennt CC Lv4; Regel im Code nicht gefunden (Annahme) |
| R8 | **Bergung nach Owner-Entscheid 2026-10-13** ist umgesetzt: Selbstbergung 3/4/6 AP sofort, Flug-Variante FC (1 AP, kein Verschleiß), kein Ertragsfaktor, strenge Ablösung. | Bergungs-Spec |
| R9 | **Kartenbudget** ca. 2.100–2.500 Rg je Karte (Inv. §1.2). | Handrechnung, nicht in Config (Inv. L10) |
| R10 | **Bau-Plan gleich für alle Eröffnungen**, nur die Reihenfolge der Pfadgebäude unterscheidet sich (Bot-Reihenfolge T30 §5b). | G4-Preisgleichheit der Pfadgebäude |

---

## A) AP-Budget je Phase

### A.1 AP-Zufluss (Referenzspieler)

Basis 12 AP, Berater +2/+3/+4 je Rang 1/2/3 (Config). Rang 2 nach 15 aktiven Solen, Rang 3 nach 45 (Config); Beförderung sofort angenommen (150/250 Cr).

| Ereignis | Sol | AP/Sol danach |
|---|---|---|
| Baumeister (CC 1, Slot 1) | 1 | 14 |
| Pfad-Berater 1 (Analytiker/Raumfahrer/Konsul) nach Pfadgebäude Lv1 | 3 | 16 |
| Pfad-Berater 2 nach zweitem Pfadgebäude (CC 3 öffnet Slot 3) | 12–13 | 18 |
| Baumeister Rang 2 | 16 | 19 |
| Pfad-Berater 1 Rang 2 | 18 | 20 |
| Vertrauens-AP ×1,05 | ab ca. 26 | 21 |
| Pfad-Berater 2 Rang 2 + vierter Berater (CC 4, Slot 4) | 28 | 24 |
| vierter Berater Rang 2 | 43 | 25 |
| Baumeister Rang 3 | 46 | 26 |
| Pfad-Berater 1 Rang 3 | 48 | 27 |
| Pfad-Berater 2 Rang 3 | 58 | 28 |
| vierter Berater Rang 3 | 73 | 29 |

| Phase | Sole | AP gesamt | Ø AP/Sol |
|---|---|---|---|
| P1 | 1–15 | 242 | 16,1 |
| P2a | 16–40 | 552 | 22,1 |
| P2b | 41–60 | 529 | 26,5 |
| P3 | 61–90 | 858 | 28,6 |

**Annahme:** Der vierte Berater kann je nach Eröffnung erst mit dem dritten Pfadgebäude kommen (Gebäude-Gate der Beratertypen), also bis Sol 34. Die Abweichung beträgt höchstens ca. 12 AP in P2a.

### A.2 Soll-Verteilung der AP (Referenzspieler)

„Erwerb" = AP für Pfad-Rg und Pfad-Credits (Flüge, Cantina-Aktionen); „Funde" = Scan + Bergung; „Sonst" = Verlegung, Erkunden nach Phase 1, Verhandeln, Kolonisten. Reparatur = Gebäude (1 AP je SP) plus Schiffe.

| Phase | Bau (inkl. Platzieren, CC) | Reparatur | Forschung | Erwerb | Funde + Erkunden | Sonst / Puffer |
|---|---|---|---|---|---|---|
| P1 Labor-First | 79 (33 %) | 10 (4 %) | 80 (33 %) | 0 | 63 (26 %) | 10 (4 %) |
| P1 Hangar-First | 79 (33 %) | 15 (6 %) | 6 (2 %) | 27 (11 %) | 44 (18 %) | **71 (29 %) Leerlauf** |
| P1 Cantina-First | 79 (33 %) | 10 (4 %) | 6 (2 %) | 25 (10 %) | 63 (26 %) | **59 (24 %) Leerlauf** |
| P2a (alle, Ø) | 115 (21 %) | 90 (16 %) | 220 (40 %) | 60 (11 %) | 15 (3 %) | 52 (9 %) |
| P2b (alle, Ø) | 95 (18 %) | 120 (23 %) | 220 (42 %) | 60 (11 %) | 5 (1 %) | 29 (5 %) |
| P3 (alle, Ø) | 90 (10 %) | 280 (33 %) | 380 (44 %) | 90 (10 %) | 0 | 18 (2 %) |

Herleitung (Annahme):
- **Bau P1:** Agrardom 1 + 10, CC 2 und 3 je 10, zwei Pfadgebäude je 1 + 10, zwei Lv2-Ausbauten je 10, Verlegung 6 = 79 AP. Identisch für alle Eröffnungen.
- **Bau P2a/P2b:** Bau-Plan aus Abschnitt B (10 bzw. 10 Posten, je 10 AP + Platzieren). Bauwesen und Baumeister-Rang senken das ab P2b um ca. 10–20 %.
- **Reparatur:** Σ `decay_rate` der Gebäude außer CC/Harvester wächst von ca. 1,8 (P1) über 3,6 (P2a) und 6 (P2b) auf ca. 8,3 SP/Sol (P3), dazu CC + Harvester (1,2 SP/Sol, nur AP).
- **Forschung:** `task_research_lead` (7 Kenntnisse auf Lv5) braucht ca. 900 AP nach Labor-Rabatt; 80 + 220 + 220 + 380 = 900 bis Sol 90 → das Ziel ist mit diesem Plan genau zum Laufende erreichbar.
- **Leerlauf P1 Hangar/Cantina:** Das Budget-Modell macht die K4-Lücke sichtbar, löst sie aber nicht. Phase 1 bleibt für diese Eröffnungen Rg-getaktet (siehe G, Risiko R4).

> ⚠️ BALANCE CONCERN: In P1 bremst AP nur Labor-First. Hangar- und Cantina-First haben 60–70 freie AP. Die neuen Pfad-Rg-Aktionen (C.2) binden davon nur ca. 25. Das Zielbild „AP bremst" gilt deshalb ab P2a vollständig, in P1 nur für Labor. Weitere Phase-1-AP-Abnehmer bleiben Thema von Utility-Bot S6 und T30 Schritt 6 (Nachschub-Signale).

### A.3 Kipppunkt „ab wann bremst Rg" (AP-Eichung)

Rg-Bedarf einer Phase = Σ Bau-AP × p_eff + Reparatur-Rg + Schiffs-Rg. Rg wird erst dann zum Engpass, wenn der Bau-Anteil an den AP über den **Kipppunkt** b* steigt:

b* = (0,95 × Einkommen − Reparatur-Rg) ÷ (AP gesamt × p_eff)

| Phase | Einkommen (Labor, C.4) | Reparatur-Rg | AP | p_eff (Plan-Mix, Rg je Bau-AP) | b* | Bau-Anteil im Plan |
|---|---|---|---|---|---|---|
| P2a | 761 | 90 | 552 | 595 ÷ 115 = 5,2 | **22 %** | 21 % |
| P2b | 740 | 120 | 529 | 515 ÷ 95 = 5,4 | **20 %** | 18 % |

Lesart: Der Referenzspieler baut knapp unter dem Kipppunkt. Wer mehr AP in Bau steckt (z. B. Forschung zurückstellt), stößt nach wenigen Solen an Rg und muss AP in Erwerb umschichten. Wer weniger baut, sammelt Rg an (Siegziel `task_self_sufficiency` profitiert). Das ist das Owner-Zielbild in einer Zahl.

---

## B) Soll-Bauplan je Eröffnung

### B.1 Phase 1, Ideal-Linie Labor-First (Sol-Tabelle)

Vorschlagswerte aus C: Start 260 Rg, Harvester y2 21 Rg/Sol, Geologie +2 / +1, Selbstbergung 3/4/6 AP. Reparatur wird bis Sol 12 aufgeschoben (Anzeige erst unter 70 %), Nachholbedarf ca. 15 Rg in Sol 13–15.

| Sol | Aktionen (AP) | AP verf./genutzt | Rg nach Aktionen | Ertrag im Tick | Rg Ende Sol |
|---|---|---|---|---|---|
| 1 | Baumeister; Harvester verlegen (6); Agrardom platzieren (1, −95) + 7 AP | 14 / 14 | 165 | +21 | 186 |
| 2 | Agrardom Lv1 fertig (3); CC 2 (10, −60) | 14 / 13 | 126 | +21 | 147 |
| 3 | Labor platzieren (1, −120) + Lv1 (10); Analytiker; 2 Felder erkunden (4) | 16 / 15 | 27 | +21 | 48 |
| 4 | Labor Lv2 (10, −25); 1 Feld (2); Scan (2) | 16 / 14 | 23 | +21 | 44 |
| 5 | Bergung mittel (4, +10); Scan (2); Geologie (10) | 16 / 16 | 54 | +21 | 75 |
| 6 | Geologie Lv1 fertig (10); Scan (2); Bergung klein (3, +4) | 16 / 15 | 79 | +21 +2 | 102 |
| 7 | Wohnhabitat Lv2 (10, −25); Bergung groß (6, +18) | 16 / 16 | 95 | +23 | 118 |
| 8 | **CC 3 (10, −90)**; Geologie (6) | 16 / 16 | 28 | +23 | 51 |
| 9 | Geologie (16) | 16 / 16 | 51 | +23 | 74 |
| 10 | Geologie Lv2 fertig (6); Scan + Bergung klein (5, +4); Bauwesen (5) | 16 / 16 | 78 | +24 | 102 |
| 11 | Scan + Bergung klein (5, +4); Bauwesen (11) | 16 / 16 | 106 | +24 | 130 |
| 12 | **Hangar platzieren (1, −120)** + Lv1 (10); Raumfahrer → **Phase 1 erfüllt** | 18 / 15 | 10 | +24 | 34 |

Großanschaffungen in P1: CC 3 (Sol 7→8, ein Sol Sparen) und zweites Pfadgebäude (Sol 9–11 Sparen). Tiefpunkte 10–30 Rg direkt nach den Käufen, sonst 45–130. Labor-First ist ab Sol 5 AP-gebunden (Forschung gegen Erkunden). **Annahme:** Die Signale liegen auf den ersten aufgedeckten Feldern; realistischer Spieler braucht 2–3 Sole länger.

### B.2 Phase 1, Hangar- und Cantina-First (Meilensteine)

| Sol | Hangar-First | Rg Ende Sol | Cantina-First | Rg Ende Sol |
|---|---|---|---|---|
| 1–2 | wie Labor | 147 | wie Labor | 147 |
| 3 | Hangar (−120) + Lv1 → Geschenk-Drohne; Raumfahrer | 48 | Cantina (−120) + Lv1; Konsul | 48 |
| 4 | Hangar Lv2 (−25, Lv2 #1); 1. Schürfflug | 44 | Cantina Lv2 (−25, Lv2 #1); ab hier Erz-Angebote ca. 2,8 Rg/Sol (E) | 47 |
| 5–7 | Schürfflug zurück Sol 6 (+5,6 E), Beifang mittel (+10); Wohnhabitat Lv2 (−25) | 98 | Selbstbergung Pool (schneller als Labor, freie AP); Wohnhabitat Lv2 | 125 |
| 8 | **CC 3 (−90)**; Schürfflug + Beifang klein | 38 | **CC 3 (−90)** | 67 |
| 9–12 | 2 Schürfflüge + 2 × Beifang klein | 141 (Sol 12) | Erz-Angebote, Rest-Pool | 138 (Sol 11) |
| | **Labor platzieren Sol 13 → Phase 1 erfüllt** | | **Labor platzieren Sol 12 → Phase 1 erfüllt** | |

- Hangar-First liegt ca. 1 Sol hinter Labor und Cantina, weil der Pool unter der strengen Ablösung Schiffszeit kostet und der große Fund auf den Frachter wartet (T30-Toleranz K3 ≤ 2 Sole: eingehalten).
- **Entscheidung im Hangar-Pfad (gewollt, kein Optimalpfad):** Frachter schon in Phase 1 kaufen (60 Rg, Vorschlag C.1) verschiebt Phase 1 um ca. 2 Sole nach hinten, bringt danach ca. +4 Rg/Sol und den großen Fund; Amortisation ca. Sol 20–22.

### B.3 Soll-Bauplan bis Sol 60 (alle Eröffnungen)

Reihenfolge der Pfadgebäude nach Bot-Profil (T30 §5b): Labor → Hangar → Cantina; Hangar → Labor → Cantina; Cantina → Labor → Hangar. Sol-Angaben gelten für den Referenzspieler (Bot ca. +4–6 Sole).

| Sol (ca.) | Labor-First | Hangar-First | Cantina-First | Rg |
|---|---|---|---|---|
| 17 | Hangar Lv2 | Labor Lv2 | Labor Lv2 | 25 |
| 18–20 | Agrardom Lv2 | **Frachter** (+ Agrardom Lv2) | Cantina Lv3 (+ Agrardom Lv2) | 25 (+60 Frachter) |
| 21 | Labor Lv3 | Labor Lv3 | Labor Lv3 | 25 |
| 24 | Uplink | Uplink | Uplink | 105 |
| 27 | **CC 4** | **CC 4** | **CC 4** | 120 |
| 30 | **Frachter** | — | — | 60 |
| 33–34 | Cantina (3. Pfad) | Cantina (3. Pfad) | Hangar (3. Pfad) | 120 |
| 36 | Wohnhabitat #2 | Wohnhabitat #2 | Wohnhabitat #2 | 65 |
| 38 | Labor Lv4 | Labor Lv4 | Hangar Lv2 | 25 |
| 40 | Cantina Lv2 | Cantina Lv2 | **Frachter** | 25 / 60 |
| **Σ P2a** | **595** | **595** | **595** | |
| 42 | Krankenstation (+25 Wk) | Krankenstation | Krankenstation | 85 |
| 44 | Agrardom Lv3 | Agrardom Lv3 | Agrardom Lv3 | 25 |
| 46 | **CC 5** | **CC 5** | **CC 5** | 150 |
| 49 | Labor Lv5 | Labor Lv5 | Labor Lv4 | 25 |
| 51 | Hangar Lv3 | Cantina Lv3 | Labor Lv5 | 25 |
| 53 | Leitstelle (+25 Wk) | Leitstelle | Leitstelle | 105 |
| 55 | Wohnhabitat #1 Lv3, #2 Lv2 | dto. | dto. | 50 |
| 57 | Cantina Lv3 | Uplink Lv2 | Hangar Lv3 | 25 |
| 59 | Uplink Lv2 | Hangar Lv3 | Uplink Lv2 | 25 |
| **Σ P2b** | **515** | **515** | **515** | |
| optional | — | Korvette (+90 Rg, Vorschlag) | — | (90) |

| Kumulierter Rg-Bedarf (Bau + Reparatur) | Labor | Hangar | Cantina |
|---|---|---|---|
| bis Phase-1-Ende (Ideal) | 535 | 535 | 535 |
| bis Sol 15 (inkl. Reparatur-Nachholung) | 555 | 560 | 555 |
| bis Sol 40 | 1.240 | 1.245 | 1.240 |
| bis Sol 60 | 1.875 | 1.880 (1.970 mit Korvette) | 1.875 |

**Ausbaustufen-Summe (`task_engineering_output`) bei Sol 60:** ca. 29 (CC 5, Labor 5, Hangar 3, Cantina 3, Agrardom 3, Wohnhabitate 3 + 2, Uplink 2, Krankenstation 1, Leitstelle 1, Harvester 1). Das passt zur Kalibrierregel „gezielt 35 um Sol 70". **Konsequenz:** Ein höheres Rg-Einkommen als hier würde dieses Siegziel direkt beschleunigen; die 85–95-%-Regel schützt also auch das Sieg-Pacing.

**Großanschaffungen je Phase (Sparfenster):** P1 CC 3 + zweites Pfadgebäude; P2a CC 4 + Frachter (oder drittes Pfadgebäude); P2b CC 5 (+ Leitstelle). Je Fenster ca. 3–5 Sole Einkommen.

---

## C) Preis- und Einkommensgerüst

### C.1 Preisgerüst (Vorschlag)

| Posten | Heute (Config) | Vorschlag | Rg je Basis-AP | Begründung |
|---|---|---|---|---|
| Startbestand | 300 | **260** | — | Mit Pool und Pfadquellen sonst Phase 1 Ideal Sol ca. 10; 260 ergibt Ideal 12–13, Bot erwartet 16–18 (B.1) |
| Ausbaustufe Nicht-CC | flat 25 | **flat 25, fix** (= 2,5 Rg je Basis-AP) | 2,5 | P1. **Flat statt gestaffelt:** eine Zahl, leicht lesbar (Owner-Transparenz). Die Staffelung entsteht von selbst über P6: Bauwesen Lv5 (−15 %) plus Baumeister Rang 3 (Vorschlag −20 %) senken die AP einer Stufe auf ca. 7, der Rg-Preis je ausgegebenem Bau-AP steigt von 2,5 auf ca. 3,6. Gestaffelt (z. B. 20/25/35/45) nur, falls P2b/P3 trotzdem überläuft (Hebel L-1 in E.4). |
| Neubau | Errichtung + 25 | **unverändert** (Pfadgebäude 95 + 25, Wohnhabitat 40 + 25 usw.) | 6–11 | G4-Preisgleichheit; „voll beim Platzieren" (Owner-Regel) |
| CC-Ausbau | Zielstufe × 30 | **unverändert** (60/90/120/150) | 6/9/12/15 | Je Phase genau eine CC-Großanschaffung, 3–5 Sole Einkommen. Hebel L-2 falls P2b überläuft: Stufe 5 auf 180–200. |
| Drohne (zusätzlich) | 300 Cr | **30 Rg + 250 Cr** | Großkauf klein | Owner: Schiffe gehören ins Rg-Budget (Frage G.3); Geschenk-Drohne bleibt kostenlos |
| **Frachter** | 500 Cr | **60 Rg + 400 Cr** | Großkauf | Owner nennt den Frachter als Großanschaffung. Credits-Anteil sinkt, weil Phase 2 Credits-knapp ist (Credits-Collapse-Befund). Amortisation über Schürfflug ca. 15 Sole. |
| Korvette | 800 Cr | **90 Rg + 650 Cr** | Großkauf | wie Frachter; kein Rg-Ertrag, Schutz/Siegziel |
| Reparatur Gebäude | 1 Rg + 1 AP je SP | **unverändert** | 1 | „Eine Zahl, zwei Währungen" (GDD §13.7) |
| Reparatur Schiff | 1 AP je 2 SP | **unverändert** (kein Rg, Owner 2026-10-11) | — | — |
| Rg→Credits-Verkauf (Gäste) | 25 Cr ± 20 %, mit Handelsvorteil | **Festpreis 22–28 Cr, ohne Handelsvorteil und Verhandlung** | — | Schließt die Arbitrage zum Import (Inv. L9): Verkauf darf nie über dem Importpreis liegen |
| Wette (Zara) | 15/20/25 Rg | unverändert | — | kleine, freiwillige Senke |
| Uplink-Import | 35 Cr/Rg, unbegrenzt, 0 AP | **unverändert als Deckel**; Messgröße K28 | — | P7. Mengendeckel nur, falls K28 > 10 % (Hebel L-5) |
| Rg-Rabatte | keine | **keine** (bestätigt) | — | Owner-Vorgabe 4 |

**Nicht geändert:** Errichtungskosten aller Gebäude, `decay_rate`-Klassen, Kenntnis-AP-Kurve, Siegziel-Schwellen.

### C.2 Einkommensgerüst (Vorschlag)

**Grundlast Harvester**

| Element | Heute | Vorschlag | Wirkung |
|---|---|---|---|
| `fresh_yield` y1/y2/y3 | 18/26/34 | **15/21/27** | Harvester-Anteil 60–70 % bei gleichzeitigen Pfadquellen; ohne Absenkung läge die Ausschöpfung in P2a bei ca. 78 % (Überlauf, C.4) |
| `resource_max` d1/d2/d3 | 240/450/660 | unverändert | Kachel y2_d2 hält ca. 21 Sole statt 17; Kartenbudget reicht bis ca. Sol 85–90 (R9) |
| Zweite Harvester-Instanz | volle Rate | **halbe Rate** (Frage G.4) | Volle Rate wäre +21–23 Rg/Sol (+60 %) und hebelt jede Eichung aus |

**Pfadquellen (eigenes Rg je Eröffnung)**

| Pfad | Quelle | Mechanik (Vorschlag) | Kosten je Aktion | Erwartung je Aktion | Durchsatz-Grenze | Rg/Sol (Referenz) | Rg je AP |
|---|---|---|---|---|---|---|---|
| Labor | **Geologie** (Harvester-Bonus) | Kurve **+2/+1/+1/+1/+1** (Σ 6, heute 3/3/2/2/2 = 12) | einmalig Forschungs-AP 20/28/36/44/52 | — | Kenntnisstufe, CC 4/5 für Lv4/5 | Lv2 3 · Lv3 4 · Lv5 6 (× Vertrauen) | Lv1–3: 84 AP → ca. 160 Rg bis Sol 40 ≈ 1,9 |
| Hangar | **Schürfflug** (neu, ersetzt die gestrichene Prospektion) | Drohne oder Frachter, Sol-Distanz 1 (Umlauf 2 Sole), Wurf normal 70 % / leicht 85 % ×0,7 | Drohne 3 Nav-AP, Frachter 4; je 3 Or | Drohne 6–10 Rg → E 5,6; Frachter 12–20 → E 11,2 | ein Flug je Schiff und Umlauf | Drohne 2,8 · Frachter 5,6 (× Auslastung) | Drohne 1,9 · Frachter 2,8 |
| Hangar | **Beifang** (neu) | Ein Schürfflug nimmt einen gescannten kleinen oder mittleren Fund ohne Zusatzkosten mit | 0 | Fundmenge | Pool | einmalig ca. 14–22 Rg | — |
| Cantina | **Erzgast** (Gästerotation) | 25 % der Gast-Plätze bieten 10–18 Rg gegen Organika (2 Rg je Or) oder Credits (28 Cr je Rg) | 2 AP + 5–9 Or bzw. 280–500 Cr | E 14 Rg | Gäste je Sol nach Konsul-Rang (Annahme Ø 0,5/1,0/1,5) | R1 1,75 · R2 3,5 · R3 5,25 (× 0,8) | 7 (zuzüglich Or/Cr) |
| Cantina | **Erz-Anliegen** (Fen, eigener Topf) | Chance 6/8/10 % je Sol (Cantina Lv1–3), Erfolg 60 %, 15–25 Rg | 2 AP | E 12 Rg | Anliegen-Takt | 0,7 / 1,0 / 1,2 | 6 |
| alle | **Fundpool** (Bergungs-Spec) | 40 Rg Start-Pool, 3/4/6 AP bzw. Flug 1 AP | — | — | 7 Signale | einmalig 40 | 1,2 (inkl. Scan) |
| alle | **Nachschub-Signale** (T30 Schritt 6, geplant) | ab Sol 8, höchstens 5, bis Sol 30 | wie Pool | E 5,7 je Signal | Sol 8–30 | ca. 20 je Run | — |
| alle | Fernexpedition (Loot), Leitstelle-Recycling, Corvan-Kauf | unverändert | — | — | selten | < 1 | — |

**Kenntnis-Rg-Erträge (Owner-Vorgabe 4, Vorschlag)**

| Kenntnis (Pfad) | Gate | Rg-Effekt je Stufe | Weitere Wirkung (Bestand) | Referenz-Durchsatz | Rg/Sol je Stufe | Lv5 Rg/Sol | Lv5 inkl. Nebenwirkung (Rg-Äquivalent) |
|---|---|---|---|---|---|---|---|
| Geologie (Labor) | Labor Lv2 + Harvester | +2/+1/+1/+1/+1 Rg/Sol am Harvester | Instabilität −3…−5 % | passiv | 1,2 (Ø) | **6,0** (6,6 mit Vertrauen) | ca. 6,5 |
| Kartografie (Hangar) | Labor Lv1 + Hangar Lv1 | **+1 Rg je erfolgreichem Schürfflug** | Nav-AP −30 % (Lv5) | Drohne + Frachter, 0,52 Erfolge/Sol | 0,52 | **2,6** | ca. 5,2 (Nav-AP-Ersparnis ca. 1 AP/Sol × 2,5) |
| Handel (Cantina) | Labor Lv1 + Cantina Lv1 | **+3 Rg je Erz-Handel** (Erzgast oder erfolgreiches Erz-Anliegen) | Handelsvorteil +12 %, +2 Angebotsplätze, Verhandlung | 0,24 Erz-Handel/Sol (R2, Auslastung 0,8) | 0,72 | **3,6** | ca. 4,6 (Handelsvorteil auf die Rg-Seite) |

- Parität: 6,5 / 5,2 / 4,6 Rg-Äquivalent je Sol bei Lv5, also ±15 % um den Mittelwert. Geologie wirkt passiv und sicher, die anderen beiden nur bei aktiver Nutzung und mit Nebenwirkung. Das ist gewollt (Art verschieden, Höhe gleich).
- Anzeige: jede Kenntnis als **eigene Zeile** am Ertrag („Schürfflug 8 + Kartografie 2 = 10 Rg"), keine versteckten Zuschläge.
- Falle aus dem Memory (Discount-Wiring): Vorschau- und Lese-Pfad mit verdrahten; kleine Beträge nicht über `round()` auf Prozentbasis lösen, deshalb absolute Rg-Zuschläge.

**Berater-AP-Boni statt Rg-Boni (Owner-Vorgabe 4, Vorschlag, entspricht ROADMAP D3)**

| Berater | Bezug | Rang 2 | Rang 3 | Hinweis |
|---|---|---|---|---|
| Baumeister | Bau-Stufen, Gebäude-Reparatur | −10 % AP | −20 % AP | Rg bleibt fix → effektiver Rg-Preis je Bau-AP steigt (P6) |
| Analytiker | Kenntnis-Stufen | −5 % | −10 % | additiv zum Labor-Rabatt, Untergrenze `project_min_cost_factor` |
| Raumfahrer | Nav-AP (Erkunden, Missionen, Schürfflug) | −1 AP je Flug ab 3 AP | −1 AP je Flug ab 2 AP | absolute Stufen, weil Prozent bei 2–4 AP wegrundet |
| Konsul | Cantina-Aktionen (Annehmen, Begegnung, Anliegen) | — | −1 AP (min. 1) | dto. |
| Stratege | Korvetten-Aktionen | −10 % | −20 % | ohne Rg-Bezug |

Diese Boni erhöhen den Rg-Kurs der Pfadaktionen (mehr Aktionen je AP), ohne Rg direkt zu verteilen. Im Modell als AP-Faktor je Aktionsart.

### C.3 Uplink-Import als Preisdeckel (Konsistenzprüfung)

| Quelle | Credits-Gegenwert je Rg | Unter Deckel 35? |
|---|---|---|
| Uplink-Import | 35 (26 mit Handelsvorteil Nexus) | Deckel |
| Erzgast gegen Credits | 28 | ja |
| Corvan-Kaufangebot | ca. 25 ± 10 % | ja |
| Botenflug → Import | 180 Cr ÷ 35 = 5,1 Rg je Flug (2 AP) | Vergleich: Schürfflug Drohne E 5,6 Rg je Flug (3 AP) → bewusst ähnlich, damit Credits- oder Rg-Flug eine echte Wahl ist |
| Rg-Verkauf an Gäste | 22–28 (Festpreis) | darunter, keine Arbitrage |

**Folge:** Der Import ist in Phase 1 unattraktiv (Uplink kostet selbst 105 Rg; 3.000+ Cr nötig, um das hereinzuholen). In Phase 2 ist er die Notbremse für Credits-reiche Cantina-Kolonien. Messung K28; liegt der Import-Anteil über 10 % des Rg-Einkommens, wirkt er wie eine AP-freie Pfadquelle und braucht einen Mengendeckel (Hebel L-5).

### C.4 Gegenrechnung Soll gegen Ist je Phase

**Soll-Einkommen** = Rg-Bedarf des Bau-Plans ÷ 0,9. **Modell neu** = Vorschlagswerte, Referenzspieler, Auslastung R6. P1-Quote = (Bedarf − Start) ÷ Einkommen, weil P1 aus dem Startbestand finanziert ist.

**Einkommen je Quelle, Modell neu (Rg je Phase, Handrechnung)**

| Quelle | Labor P1 | Labor P2a | Labor P2b | Hangar P1 | Hangar P2a | Hangar P2b | Cantina P1 | Cantina P2a | Cantina P2b |
|---|---|---|---|---|---|---|---|---|---|
| Harvester (Grundlast) | 315 | 524 | 444 | 315 | 524 | 444 | 315 | 524 | 444 |
| Geologie | 27 | 109 | 122 | 0 | 57 | 102 | 0 | 57 | 102 |
| Schürfflug (Drohne + Frachter) | 3 | 94 | 92 | 34 | 149 | 92 | 0 | 13 | 77 |
| Kartografie-Zuschlag | 0 | 7 | 14 | 0 | 22 | 18 | 0 | 0 | 3 |
| Cantina (Erzgast + Anliegen) | 0 | 12 | 59 | 0 | 12 | 44 | 27 | 88 | 77 |
| Handel-Zuschlag | 0 | 0 | 4 | 0 | 0 | 2 | 0 | 19 | 50 |
| Funde (Pool, Beifang, Nachschub, Loot) | 40 | 15 | 5 | 22 | 45 | 5 | 40 | 15 | 5 |
| Corvan / Import (Annahme) | 0 | 0 | 0 | 0 | 0 | 0 | 0 | 10 | 15 |
| **Σ Einkommen** | **385** | **761** | **740** | **371** | **809** | **707** | **382** | **726** | **768** |
| je Sol | 25,7 | 30,4 | 37,0 | 24,7 | 32,4 | 35,4 | 25,5 | 29,0 | 38,4 |

**Bilanz und Kennzahlen**

| Phase | Kennzahl | Labor | Hangar | Cantina | Soll |
|---|---|---|---|---|---|
| P1 (Sol 1–15) | Bedarf | 555 | 560 | 555 | — |
| | Quote (Bedarf − 260) ÷ Einkommen | 77 % | 81 % | 77 % | Rücklage für P2a |
| | Rücklage Ende Sol 15 | 90 | 71 | 87 | 60–120 |
| | Phase-1-Ende Ideal | Sol 12 | Sol 13 | Sol 12 | Abstand ≤ 2 |
| P2a (Sol 16–40) | Bedarf (inkl. Reparatur 90, Frachter) | 685 | 685 | 685 | — |
| | **Quote** | **90 %** | **85 %** | **94 %** | 85–95 % |
| | Harvester-Anteil | 69 % | 65 % | 72 % | 60–70 % |
| | Pfad- und Fund-Anteil | 31 % | 35 % | 28 % | 30–40 % |
| P2b (Sol 41–60) | Bedarf (inkl. Reparatur 120) | 635 | 635 | 635 | — |
| | **Quote** | **86 %** | **90 %** | **83 %** | 85–95 % |
| | Harvester-Anteil | 60 % | 63 % | 58 % | 60–70 % |
| Sol 60 | Rg-Bestand (Rücklage + Überschüsse) | ca. 270 | ca. 270 | ca. 260 | ≥ 150 (Siegziel Selbstversorgung möglich) |

**Ist heute (Config, gleicher Referenzspieler, Annahme)**

| Phase | Labor | Hangar | Cantina | Harvester-Anteil | Befund |
|---|---|---|---|---|---|
| P1 | Quote 53 % (Start 300, Harvester 26, Geologie 3/3) | ähnlich | ähnlich | ca. 82 % | Phase 1 Ideal ca. Sol 10–11; der Bot (17,5) wird nur durch Ineffizienz in den Korridor gebracht |
| P2a | ca. 897 Rg → Quote 76 % | ca. 806 → 85 % | ca. 794 → 86 % | 72–80 % | Labor deutlich reicher (Geologie Σ 12), Hangar ohne Rg-Mission, Cantina nur Zufall |
| Bot gemessen | ca. 17 Rg/Sol, > 90 % Harvester-Topf (Owner-Angabe) | | | | Abstand Ideal ↔ Bot ca. Faktor 2; Ursache offen (Resttopf L1, y1-Kacheln, Verlegung, Kartenende). **Muss vor jeder Kalibrierung geklärt werden** (F.1, Risiko R1). |

### C.5 Empfindlichkeit und Bestandsverlauf

**Kachel-Ertragsstufe (Labor, P1):** y1 (15) −66 Rg bis Sol 11 → Phase 1 Ideal ca. Sol 15; y3 (27) +66 → ca. Sol 10. Die H2-Gegensatzpaar-Wahl macht daraus eine Entscheidung (hoher Ertrag gegen kurze Laufzeit). Spanne ca. 5 Sole, wie heute.

**Rg-Bestand Labor-First, Sol 15–60 (Netto-Einkommen P2a 26,8/Sol, P2b 31/Sol):**

| Sol | Kauf | Bestand danach |
|---|---|---|
| 15 | — | 97 |
| 17 / 19 / 21 | Hangar Lv2, Agrardom Lv2, Labor Lv3 | 126 / 155 / 184 |
| 24 | Uplink (105) | 159 |
| 27 | **CC 4 (120)** | 119 |
| 30 | **Frachter (60)** | 139 |
| 33 | Cantina (120) | **99** |
| 36 / 38 / 40 | Wohnhabitat #2, Labor Lv4, Cantina Lv2 | 114 / 143 / 172 |
| 42 / 44 | Krankenstation, Agrardom Lv3 | 149 / 186 |
| 46 | **CC 5 (150)** | **98** |
| 49 / 51 / 53 | Labor Lv5, Hangar Lv3, Leitstelle | 166 / 203 / 160 |
| 55 / 57 / 59 | Wohnhabitate, Cantina Lv3, Uplink Lv2 | 172 / 209 / 221 |

Tiefpunkte ca. 100 direkt nach Großanschaffungen, nie unter 90 außerhalb von Phase 1. Wer eine Großanschaffung früher will (z. B. CC 4 an Sol 22), muss 3–5 Sole sparen oder AP in Erwerb umschichten. Das ist das Zielbild.

> ⚠️ BALANCE CONCERN: P3 (Sol 61–90) ist nur grob gerechnet. Ohne Gegenmaßnahme läge das Einkommen bei ca. 39 Rg/Sol gegen ca. 29 Rg/Sol Bedarf (Quote ca. 75 %). Bremsend wirken das Kartenbudget (Harvester + Geologie ziehen bis Sol 60 ca. 1.540 Rg, bis Sol 90 ca. 2.400, also an der Budgetgrenze) und Stufenverluste durch Sturm. Das Rechenmodell muss P3 mit Kartenbudget je Seed rechnen, bevor Hebel L-1/L-2 gezogen werden.

---

## D) Balance-Check der drei Pfade

### D.1 Eigenes Rg-Einkommen im Verhältnis zur AP-Last (Referenz P2a)

| Pfad | Eigene Quelle | Rg/Sol (Mitte P2a) | Laufende AP/Sol dafür | Einmal-Invest | Zusatzwährung | Rg je laufendem AP | Kenntnis Lv5 (Rg-Äq./Sol) |
|---|---|---|---|---|---|---|---|
| Labor | Geologie Lv2–3 | 3–4 | 0 | 48–84 Forschungs-AP | — | ∞ (passiv), einmalig ca. 1,9 Rg/AP | 6,5 |
| Hangar | Schürfflug Drohne (+ Frachter) | 2,1 (+ 4,2) | 1,1 (+ 1,5) | Hangar Lv2 25 Rg; Frachter 60 Rg + 400 Cr | 1,5 Or/Sol je Schiff | 1,9 / 2,8 | 5,2 |
| Cantina | Erzgast + Erz-Anliegen | 2,2–3,8 | 0,5–0,8 | Cantina Lv2/3 je 25 Rg | 3–6 Or/Sol oder Credits | ca. 5 | 4,6 |

| Pfad-Rg Sol 16–40 (eigener Pfad) | brutto | abzüglich Pfad-Invest (Frachter, Lv2/3) | abzüglich pfadneutraler Funde |
|---|---|---|---|
| Labor-First | 109 | 109 (Labor Lv2/3 zählt ohnehin zum Bau-Plan) | 109 |
| Hangar-First | 201 | 141 | 111 |
| Cantina-First | 107 (+ 10 Credits-Kauf) | 107 | 117 |

**Urteil (Annahme):** Netto liegen die drei Pfade bei 109–117 Rg in P2a, also innerhalb ±4 %. Brutto ist Hangar höher, zahlt dafür den Frachter und trägt in Phase 1 den Nachteil (ca. 1 Sol später). Labor ist der einzige Pfad ohne laufende AP-Last, hat dafür die kleinste Rate. Cantina hat den besten AP-Kurs, zahlt aber in Organika oder Credits. Unterschiedliche Art, gleiche Höhe: das ist die Parität aus T30 F1 („Wirkungs-Parität").

### D.2 Kenntnis-Parität

Bei gleicher Forschungsinvestition (180 AP bis Lv5) liefern die drei Pfad-Kenntnisse 4,6–6,5 Rg-Äquivalent je Sol (C.2). Weil alle Kenntnisse das Labor voraussetzen, kommt Kartografie bzw. Handel in Hangar- und Cantina-First erst nach dem zweiten Pfadgebäude (ca. Sol 13). Das gleicht den Vorsprung der Geologie in Labor-First (ab Sol 6) nicht vollständig aus. Ausgleich: Labor-First hat dafür in P1 keine laufende Pfadquelle.

> ⚠️ BALANCE CONCERN: Die Dreifach-Kolonie stapelt drei Pfadquellen plus drei Kenntnis-Zuschläge (P2b: Pfadanteil bis 42 %). Bremsen sind die Auslastung (Schiffszeit und Gäste konkurrieren mit Credits- und Siegziel-Aktionen, R6) und die AP-Last von Hangar und Cantina. Wird die Auslastung im Bot deutlich höher gemessen als 55 % (Hangar) bzw. 80 % (Cantina), läuft P2b über. Messen über K26/K29, nicht vorab nachregeln.

---

## E) Messplan und Kalibrierungsschleife

### E.1 RunReport-Fixes (Voraussetzung, vor jeder Wertänderung)

| # | Fix | Warum |
|---|---|---|
| M1 | **Rg-Buchungsjournal mit Quellen-Schlüssel**: jede Rg-Änderung (Gutschrift und Abzug) wird mit einem Schlüssel gebucht: `harvester_base`, `geology`, `find_self`, `find_flight`, `mission_ore`, `mission_loot`, `cantina_guest`, `cantina_concern`, `merchant_buy`, `import`, `recycle`; Senken `build_place`, `build_levelup`, `cc_upgrade`, `repair`, `ship_purchase`, `cantina_sell`, `wager`. | Ersetzt den Resttopf (Inv. L1): heute landen Import, Recycling und Geologie im Harvester-Topf, Fen/Wette/Händler nirgends, `event` ist immer 0 |
| M2 | Harvester-Ertrag aus dem Tick **direkt** buchen (Basis und Geologie getrennt, nach Vertrauen und Personal) | Grundlast-Anteil (Owner-Ziel 60–70 %) wird erst damit messbar |
| M3 | `rejections` je Code und Sol, `resource_limit` zusätzlich nach Zielposten (Platzieren, Stufe, CC, Schiff) | Owner-Messgröße „wenige Abweisungen" |
| M4 | Utility-Bot: Sparziel-Sole (`SavingsGoal` aktiv) je Ziel und Phase | zeigt Sparfenster, auch wenn der Bot nie abgewiesen wird |
| M5 | `regolith_path_attribution` auf die neuen Schlüssel umstellen (Pfad A = Geologie, B = Schürfflug + Kartografie + Beifang, C = Cantina + Handel; Funde separat) | K8–K10 aus T30 werden damit endlich berechenbar (Inv. L11) |

### E.2 Neue Kennzahlen (Vorschlag, Fortsetzung der K-Nummern)

| ID | Definition | Ziel | Warnschwelle |
|---|---|---|---|
| K24 | `resource_limit`-Abweisungen je Lauf (Rg) | ≤ 3 je Lauf | > 8 |
| K25 | Sole mit Rg < 30 ab Sol 16 (Anteil) und längste Serie | ≤ 10 %, Serie ≤ 3 | > 20 % oder Serie > 5 |
| K26 | Rg-Ausschöpfung je Phase = Σ Senken ÷ Σ Quellen | 85–95 % (P2a, P2b) | < 80 % (Überlauf) oder > 98 % (Mangel) |
| K27 | Harvester-Grundlast-Anteil (`harvester_base` ÷ Σ Quellen) ab Sol 16 | 60–70 % | < 55 % oder > 75 % |
| K28 | Import-Anteil (`import` ÷ Σ Quellen) | ≤ 5 % | > 10 % |
| K29 | Pfad-Rg netto je Eröffnung Sol 16–40 und 41–60 | Abstand ≤ 25 % zum Mittelwert | > 35 % |
| K30 | Ungenutzte AP ab Sol 16 (Anteil am Zufluss) | ≤ 8 % | > 15 % (dann bremst nicht AP, sondern nichts) |
| K31 | Modell-Abweichung: \|Bot − Modell (Bot-Profil)\| je Quelle und Phase | ≤ 15 % | > 25 % (Modell oder Bot falsch) |
| K32 | Sparfenster je Phase (M4): Anzahl und Länge | 1–2 je Phase, ≤ 5 Sole | > 3 Fenster oder > 8 Sole |

Bestehende K1–K23 bleiben; K3 (Phase-1-Sol) und K7 (Siegquote, Bot ca. 50 %, Warnung > 65 %) bleiben Abnahmemaßstab. Die Bot-Siegquote ist kein Tuning-Ziel.

### E.3 Rechenmodell `tools/economy-model.php` (Konzept)

**Rolle:** Quelle der Wahrheit für die Bilanz. GDD §13.7 verweist künftig auf das Modell statt auf eine Tabelle; dieses Dokument liefert nur die Startannahmen.

| Aspekt | Konzept |
|---|---|
| Eingaben | (1) Config, geladen über den Laravel-Bootstrap (`config('game')`, `config('buildings')`, `config('missions')`, `config('ships')`, `config('knowledge')`), keine kopierten Zahlen. (2) Szenario-Datei je Eröffnung: Bau-Plan als Prioritätsliste (Ziel, frühester Sol), Forschungs-Reihenfolge, Berater-Reihenfolge, Kachelfolge (y/d), Auslastung je Pfad (R6), Effizienzfaktor (Referenzspieler 1,0; Bot-Profil gemessen). |
| Rechnung | Deterministisch Sol 1–100 mit **Erwartungswerten** (kein Zufall): AP-Zufluss nach A.1, Kauf sobald Rg und AP reichen, Harvester mit Kachel-Restvorkommen, Verlegung und Kartenbudget, Reparatur nach `decay_rate`, Pfadquellen nach Durchsatz × Auslastung × Erfolgschance, Kenntnis- und Berater-Boni. |
| Ausgaben | Sol-Tabelle (AP je Kategorie, Rg je Quelle und Senke, Bestand), Phasen-Zusammenfassung mit K24–K32-Prognose, Empfindlichkeit y1/y2/y3. Markdown und JSON. |
| Vergleichsmodus | liest RunReport-JSON (M1) und stellt Bot und Modell je Quelle und Phase gegenüber (K31). |
| Ort und Form | CLI (`php tools/economy-model.php --opening=labor --tile=y2_d2`) oder Artisan-Command; optional als Tab im Playtest-Dashboard. Ersetzt den Regolith-Bilanz-Rechner (Artifact) aus dem Memory. |
| Tests (TDD) | Unit-Tests gegen Mini-Szenarien mit fester Config-Fixture (z. B. „2 Sole, ein Kauf"), nicht gegen Live-Config-Werte, damit Balance-Änderungen keine Tests brechen. |

### E.4 Kalibrierungsschleife

1. **Config ändern** (höchstens 1–2 Hebel je Runde).
2. **Modell rechnen**: 3 Eröffnungen × y1/y2/y3 → K26/K27/K29 im Soll? Wenn nein: zurück zu 1, ohne Batch.
3. **Bot-Batch** (24 Läufe, Owner wählt 8/16/24, Dauer vorab nennen) bis Sol 60 für Bilanz, volle Läufe nur für K7.
4. **K31 prüfen**: Weicht der Bot je Quelle mehr als 25 % ab, zuerst klären, ob Bot-Fehler (Regel fehlt, Pfad nicht genutzt) oder Modellannahme (Auslastung, Effizienz). Erst danach Balance.
5. **Entscheiden**: K24–K32 im Soll → fertig. Sonst Hebel nach Tabelle:

| Hebel | Wirkung | Wann |
|---|---|---|
| L-1 Ausbaustufe gestaffelt (Stufe 4/5 teurer) | mehr Bedarf in P2b/P3 | K26 < 80 % in P2b/P3 |
| L-2 CC Stufe 5 teurer (180–200) | ein Sparfenster mehr in P2b | K32 = 0 in P2b |
| L-3 Harvester-Ertrag ±1 Stufe | alle Phasen, alle Pfade gleich | K27 außerhalb 60–70 % |
| L-4 Pfadquelle einzeln (Schürfflug-Spanne, Erzgast-Anteil, Geologie-Kurve) | nur ein Pfad | K29 > 25 % |
| L-5 Import-Mengendeckel je Uplink-Stufe | Credits→Rg begrenzen | K28 > 10 % |
| L-6 Startbestand ±20 | nur Phase 1 | K3 außerhalb 15–20 (Bot) |

---

## F) Umsetzungs-Schritte und Abhängigkeiten

| # | Schritt | Inhalt | Abhängig von | Messung | Aufwand (Annahme) |
|---|---|---|---|---|---|
| F0 | Utility-Bot S3 abschließen | ohne Wirtschaftsänderung, wie in der Bergungs-Spec §8 | — | S3-Messung | läuft |
| F1 | **Mess-Infrastruktur** | M1–M5, K24–K32 in `OpeningComparison` (TDD) | F0 | **Baseline 24 Läufe mit heutiger Config** bis Sol 60 | M |
| F2 | **Rechenmodell** | E.3, Validierung gegen F1-Baseline (K31 ≤ 15 % je Quelle); Effizienzfaktor des Bots bestimmen | F1 | keine (nur Modell) | M |
| F3 | **Bergungs-Umbau** (entschieden) | 3/4/6 AP sofort, Flug FC, `yield_factor` raus, Generatorregel große Funde | F0 | Modell; Batch zusammen mit F4 | M |
| F4 | **Config-Paket Sockel** (Frage G.2) | Harvester 15/21/27, Start 260, Geologie 2/1/1/1/1, Rg-Verkauf Festpreis ohne Handelsvorteil; Tests mit Wert-Pins (`HarvesterFreshYieldConfigTest`, `OnboardingTest` u. a., Inv. §4.3), `ResetPlayer`-Szenarien und Kommentare, `game-reference.md` | F2 | Batch A (24 Läufe) | K |
| F5 | **Hangar: Schürfflug + Beifang** | Missionskatalog, `FindFlightService`/`HangarService`, Fundkarte, Bot-Regel bzw. Utility-Quelle | F3 | Modell, dann Batch B | M |
| F6 | **Cantina: Erzgast + Erz-Anliegen** | `BarService` (eigener Anliegen-Topf, Gast-Anteil), Lang-Texte (content-writer) | F4 | Batch B | M |
| F7 | **Kenntnis-Rg-Zuschläge** Kartografie und Handel | Config-Keys je Stufe, Ertragszeilen in Vorschau und Buchung, `KnowledgeEffectDescriptionService` | F5, F6 | Batch B | K–M |
| F8 | **Schiffe mit Rg-Preis** (Frage G.3) | `config/ships.php`, Kaufpfad inkl. Nexus-Kredit, Hangar-UI, Bot | F4 | Batch C | K |
| F9 | **`event_ruin` + Zweitinstanz-Rate** (Frage G.4) | Generator setzt eine Ruine (eigener Seed-Strom, Ring 3), Rate der zweiten Instanz halbiert; macht `mission_harvester_salvage` und `mission_ruin_expedition` erreichbar | F4 | Batch C | M |
| F10 | **Berater-AP-Boni** (D3) | `project_cost_bonus` je Rang und Aktionsart, absolute Stufen für kleine Kosten | F7 | Batch D | M |
| F11 | Doku | GDD §3/§4b/§5/§8b/§12/§13.7 zahlenfrei auf Prinzipien P1–P7 und Verweis auf das Modell; `game-reference.md` nach jedem Batch | laufend | — | K |

**Reihenfolge-Begründung:** Erst messen können (F1) und rechnen können (F2), sonst ist jede Änderung blind; die heutige Bot-Lücke (17 statt ca. 30 Rg/Sol) muss vorher erklärt sein. Mit dem Buchungsjournal lassen sich F5–F7 in **einem** Batch messen, weil jede Quelle eigen gebucht wird. F10 zuletzt, weil es die AP-Seite verschiebt und sonst die Rg-Eichung überlagert.

**Hinweis Bot:** Jede neue Quelle braucht eine Utility-Quelle (Schürfflug, Erzgast, Erz-Anliegen, Frachter-Rg-Preis im Sparziel). Ohne sie misst der Batch eine Eröffnung, die ihre Quelle nicht nutzt (Fehlerfamilie „fehlende Regel" aus dem Memory).

---

## G) Risiken und offene Owner-Fragen

### G.1 Risiken

| # | Risiko | Folge | Gegenmaßnahme |
|---|---|---|---|
| R1 | **Bot erreicht nur ca. 55 % des Referenz-Einkommens** (17 statt ca. 30 Rg/Sol) | Eichung auf den Referenzspieler lässt den Bot verhungern; Siegquote fällt deutlich unter 50 % | F1/F2 zuerst; Effizienzfaktor messen; Bot-Fehler von Balance trennen (Memory-Methode) |
| R2 | Pfadquellen stapeln in P2b/P3 | Überlauf, Siegziel Ausbaustufen zu früh | Auslastung messen (K29), Hebel L-1/L-2/L-4 |
| R3 | Zweite Harvester-Instanz | +60 % Einkommen auf einen Schlag | halbe Rate (G.4), Kartenbudget im Modell |
| R4 | Phase 1 bleibt für Hangar/Cantina AP-leer | Zielbild „AP bremst" gilt dort erst ab P2a | offen benennen; S6/T30 Schritt 6 |
| R5 | Hangar-First in Phase 1 ca. 1 Sol hinten (Pool kostet Schiffszeit) | K3/K6-Abstand am Rand | Beifang (Default); Fallback: kürzere Bergungsflüge |
| R6 | Credits-Collapse nach Phase 1 verschärft sich durch Frachter-Credits und Erzgast-Credits | Credits-Pfadlücke (Memory 2026-08-17) | Frachter-Credits gesenkt; Erzgast auch gegen Organika; K28 |
| R7 | Kartenbudget implizit (Inv. L10) | P3-Rechnung unsicher | Budget als Generator-Invariante explizit machen, im Modell je Seed |
| R8 | Viele neue Zahlen (Schürfflug, Erzgast, Zuschläge, Schiffs-Rg) | Verständlichkeit (Owner-Priorität) | jede Zahl vor der Entscheidung sichtbar, Zuschläge als eigene Zeilen, höchstens eine neue Regel je Pfad |
| R9 | Geologie-Abschwächung (Σ 12 → 6) | Labor-First fühlt sich schwächer an | Labor behält das unbegrenzte AP-Ziel (Forschung) und den frühesten Ertrag; messen über K29 |
| R10 | Tests und Szenarien pinnen Werte | rote Tests bei F4/F5 | Liste Inv. §4.3, `ResetPlayer` gemäß Agenten-Regel nachziehen |

### G.2 Offene Owner-Fragen (mit Default)

1. **Pfad-Rg „im Mittel kalkulierbar, im Einzelfall zufällig"?** Die Linie vom 2026-10-09 lautete „kein planbarer Rg aus Hangar und Cantina". Dein neuer Auftrag verlangt 30–40 % aus Missionen, Funden und Cantina mit eigenem Einkommen je Eröffnung. Das geht nur mit wiederholbaren Quellen: Schürfflug mit Wurf und Ertragsspanne, Erzgast und Erz-Anliegen mit Zufallstakt. **Default: ja.** Jede einzelne Aktion bleibt unsicher, nur der Erwartungswert ist geeicht.
2. **Sockel und Kenntnisse neu?** Harvester 18/26/34 → 15/21/27, Start 300 → 260, Geologie 3/3/2/2/2 → 2/1/1/1/1; neu Kartografie +1 Rg je erfolgreichem Schürfflug und Stufe, Handel +3 Rg je Erz-Handel und Stufe. **Default: ja**, als ein Config-Paket (F4/F7) mit Messung. Ohne Absenkung läuft P2a mit Pfadquellen auf ca. 78 % Ausschöpfung (Überlauf).
3. **Schiffe kosten Regolith?** Frachter 60 Rg + 400 Cr, Korvette 90 Rg + 650 Cr, Zusatzdrohne 30 Rg + 250 Cr; Geschenk-Drohne bleibt frei. **Default: ja.** Nur so wird der Frachter die von dir genannte Großanschaffung.
4. **Zweite Harvester-Instanz mit halber Rate und Ruine im Generator?** Volle Rate wäre +60 % Einkommen und bricht jede Eichung; der Generator setzt heute nie `event_ruin`, Weg B ist tot. **Default: ja** (halbe Rate; eine Ruine je Karte auf Ring 3, eigener Seed-Strom).

**Bewusst als Designer-Default gesetzt (Veto möglich, keine Frage):** Beifang beim Schürfflug; Rg-Verkauf an Gäste zum Festpreis ohne Handelsvorteil; Ausbaustufe bleibt flat 25; CC-Formel bleibt; Berater-AP-Boni mit absoluten Stufen für 2–4-AP-Aktionen.
