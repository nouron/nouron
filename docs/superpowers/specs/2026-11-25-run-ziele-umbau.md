# Spec: Umbau der Run-Ziele (Phase-2-Aufgaben), 2026-11-25

**Status:** Verbindliche Vorlage für die Umsetzung (TDD, game-developer). Zielwerte sind **Config-Vorschläge**, keine Owner-Entscheidung. Offene Owner-Fragen stehen in §11.
**Grundlage:** Bewertung `docs/superpowers/specs/2026-11-24-ziele-und-spaetspiel-bewertung.md`; Owner-Entscheidungen vom 2026-11-24 (unten §0); GDD §15 „Aufgabenpool", §18.1, §18.4.
**Datenbasis:** Bot-Batch M4 (`storage/logs/playtest/20261124_m4/`, utility-Bot, Seeds 1–8 × Labor/Hangar/Cantina, Profile default und focus, bis Sol 100). Einzelwerte, die hier neu ausgelesen wurden, nennen Lauf und Sol.

Kennzeichnung (wie in der Bewertung):
- **gemessen**: aus M4-Logs, Lauf genannt
- **gerechnet**: aus Config-Werten, nicht gemessen
- **Annahme**: Deutung oder Schätzung ohne Beleg

---

## 0. Owner-Entscheidungen 2026-11-24 (verbindlich)

1. Jede Ziehung enthält **mindestens ein mittelfristiges und mindestens ein langfristiges Ziel**. Drei Ziele je Run wie bisher. (Die Leitplanke „höchstens ein Langläufer" ist abgelehnt.)
2. **Forschungsvorsprung** = Summe der Kenntnisstufen.
3. **Expertenstab** = Summe der Berater-Ränge, ohne Beschleunigungsmechanik (revidiert 2026-09-27).
4. **Selbstversorgung** = „mehr erzeugen als verbrauchen" statt Vorrat halten.
5. **Kein** „Handelspartner/Expeditionen zählen erst ab der Ziehung" (in Phase 2 wären evtl. nicht genug Felder für Expeditionen offen). Das Problem der frühen Erfüllung bleibt und braucht eine andere Lösung → §6, Owner-Frage.
6. T61 erst nach dieser Reparatur, nicht Teil dieser Spec.

Ingenieursleistung: Mechanik bleibt, nur neu kalibrieren.

---

## 1. Klassifikation: mittelfristig und langfristig

### 1.1 Was die Klassen bedeuten

Die Klasse ist ein **Zeithorizont**, keine Schwierigkeit:

- **Mittelfristig** (Config `horizon: 'medium'`): Das Ziel misst eine **Tätigkeit oder ein Einkommen**. Fortschritt ist ab Phase-2-Beginn sofort möglich, ohne spätes Tor. Wer gezielt spielt, schafft es im **vorderen Teil** des Kalibrier-Korridors.
- **Langfristig** (Config `horizon: 'long'`): Das Ziel misst den **Reife- oder Ausbaugrad der ganzen Kolonie**. Es braucht späte Tore (Kommandozentrale Stufe 4/5, lange Dienstzeit, volle Agrardom-Stufe, eine hohe Vertrauensstufe) und lange gehaltene Zustände. Wer gezielt spielt, schafft es im **hinteren Teil** des Korridors.

Die Owner-Kalibrierregel bleibt unverändert (gezielt Sol 70–85, nicht gezielt frühestens Sol ~90 oder gar nicht). Die Klassen teilen diesen Korridor nur auf (Vorschlag, Annahme):

| Klasse | gezielt gespielt | nicht gezielt |
|---|---|---|
| mittelfristig | Sol ~70–75 | frühestens Sol ~90 oder nie |
| langfristig | Sol ~78–85 | frühestens Sol ~90 oder nie |

Warum das zum Sieg-Korridor passt (Annahme, aus §18.4 abgeleitet): Zwei mittelfristige Ziele in einer Ziehung ergeben den „sehr guten Run mit Glück" um Sol 70–80. Mittelfristig plus langfristig ergibt, weil beide um denselben AP-Pool konkurrieren, den realistischen Sieg um Sol 85–95.

### 1.2 Zuordnung aller acht Ziele

| Spielbegriff | Schlüssel | Klasse | Füttert | Begründung |
|---|---|---|---|---|
| Handelspartner | `task_trade_volume` | mittelfristig | Cantina | Zähler aus Einkäufen, ab Phase-1 möglich, kein spätes Tor |
| Expeditionen | `task_expedition_coverage` | mittelfristig | Hangar | Zähler aus Missionen, wiederholbare Missionen ab Sol 1 |
| Forschungsvorsprung (neu) | `task_research_lead` | mittelfristig | Labor (Kenntnisse brauchen zusätzlich Zweitgebäude) | Mit dem Labor zuerst als Summe mit Breite bis Stufe 3 erreichbar, ohne die Tore für Stufe 4/5; ohne Labor-Eröffnung deutlich später, daher Zielwert je Eröffnung (§3.1) |
| Kreditreserve | `task_credit_reserve` | mittelfristig | neutral, Cantina am schnellsten | Einkommensziel, kein spätes Tor |
| Expertenstab (neu) | `task_senior_advisors` | langfristig | neutral | Braucht den vierten Berater-Platz (Kommandozentrale Stufe 4) und Dienstzeit |
| Ingenieursleistung | `task_engineering_output` | langfristig | neutral | Hohe Summe braucht Kommandozentrale Stufe 4/5 (Stufen-Gate) |
| Selbstversorgung (neu) | `task_self_sufficiency` | langfristig | neutral | Braucht Agrardom Stufe 3 (Kommandozentrale Stufe 3), eine große Kolonie und eine lange Serie |
| Kolonieblüte | `task_colony_prosperity` | langfristig | neutral (Vertrauen) | Lange gehaltener Zustand der ganzen Kolonie; T61 macht ihn aktiver |

**Das Muster dahinter (Annahme, als Designregel vorgeschlagen):** Die mittelfristigen Ziele sind die **Fokus-Ziele**: Jedes Fokus-Gebäude füttert genau eines (Labor → Forschungsvorsprung, Hangar → Expeditionen, Cantina → Handelspartner), dazu ein neutrales Einkommensziel. Die langfristigen Ziele sind **Kolonie-Ziele** und hängen an keinem Fokus-Gebäude.

### 1.3 Fairness je Eröffnung

Die Ziehung hängt nur am Seed, alle drei Eröffnungen bekommen dieselben drei Ziele (§2.3).

| Eröffnung | eigenes mittelfristiges Ziel | fremde mittelfristige | langfristige |
|---|---|---|---|
| Labor | Forschungsvorsprung | Handelspartner, Expeditionen | alle neutral |
| Hangar | Expeditionen | Handelspartner, Forschungsvorsprung | alle neutral |
| Cantina | Handelspartner (+ Kreditreserve am schnellsten) | Expeditionen, Forschungsvorsprung | alle neutral |

- Jede Eröffnung hat genau ein eigenes Fokus-Ziel; durch die symmetrische Ziehung ist die Chance, es zu ziehen, für alle drei gleich groß (gerechnet aus der Regel in §2).
- **Schieflage Cantina (Annahme):** Kreditreserve ist neutral, fällt aber mit Cantina am schnellsten (gemessen M4, focus Cantina Sol 39–40). Die Kombo-Regel (höchstens ein Wirtschaftsziel) verhindert Handelspartner plus Kreditreserve in derselben Ziehung, die Cantina bekommt aber häufiger „ein schnelles Ziel" als die anderen. Behandlung über §6 (dieselbe Option gilt für Kreditreserve).
- **Schieflage Labor (Annahme):** Der Labor-Pfad füttert sein mittelfristiges Ziel langsamer als Hangar und Cantina ihre (Forschung kostet AP aus dem Pool, Einkäufe und Missionen laufen nebenbei). Mit P5-A (§6) gleichen sich die Fokus-Ziele an; ohne P5-A gewinnen Hangar und Cantina „2 mittelfristige"-Ziehungen früher.
- **Kein Ziel, das eine Eröffnung nebenbei erfüllt?** Heute doch: Handelspartner (Cantina) und Expeditionen (Hangar) fallen beim nicht gezielt spielenden Bot um Sol 27–39 (gemessen, Bewertung §1.3). Das ist Problem 5 (§6). Forschungsvorsprung als Summe fällt beim nicht gezielt spielenden Labor-Bot nicht nebenbei (gemessen, §3.1).
- Die langfristigen Ziele sind alle neutral; keine Eröffnung hat dort einen Vorteil durch die Ziel-Definition. Unterschiede kommen nur aus der Wirtschaft der Pfade (z. B. Geologie-Regolith für Ingenieursleistung, Annahme).

---

## 2. Ziehungsregel

### 2.1 Heute (Code `RunProgressService::drawObjectives()`, Config `run.task_pool`, `run.tasks.*.category`)

1. Der Pool (`run.task_pool`, sonst alle Schlüssel aus `run.tasks`) wird nach `crc32(seed|task_key)` sortiert (reine Funktion, kein globaler Zufall).
2. Der Reihe nach wird genommen, bis drei Ziele da sind; ein zweites Ziel der Kategorie `economy` wird übersprungen (Kombo-Regel).
3. Fallback: Reichen die Ziele nicht, wird mit Nicht-Wirtschafts-Zielen aufgefüllt.
4. Gespeichert werden die Ziele in Ziehungsreihenfolge mit `target_value` aus Config.

Die Kategorie (`economy`, `research`, `personal` …) bleibt nur für die Kombo-Regel in Gebrauch.

### 2.2 Neu

Neues Config-Feld je Ziel: `horizon` mit den Werten `'medium'` oder `'long'` (Zuordnung §1.2). Die Kategorie bleibt unverändert für die Kombo-Regel.

Algorithmus in Worten:

1. **Reihenfolge wie heute:** Den Pool nach `crc32(seed|task_key)` sortieren. Diese Rangfolge ist die einzige Zufallsquelle.
2. **Erstes Ziel:** das erste mittelfristige Ziel der Rangfolge.
3. **Zweites Ziel:** das erste langfristige Ziel der Rangfolge, das die Kombo-Regel nicht verletzt.
4. **Drittes Ziel:** das erste noch nicht gewählte Ziel der Rangfolge (gleich welcher Klasse), das die Kombo-Regel nicht verletzt.
5. **Speicherreihenfolge:** Die drei gewählten Ziele werden in ihrer Rangfolge-Reihenfolge gespeichert, nicht in der Wahlreihenfolge. Das erste gespeicherte Ziel ist das von Anfang an sichtbare (GDD §18.1, gestaffelte Enthüllung). So ist das sichtbare Ziel nicht immer mittelfristig.
6. **Fallbacks:**
   - Hat der Pool keine mittelfristigen (oder keine langfristigen) Ziele, entfällt der jeweilige Schritt; Schritt 4 füllt auf drei auf. Kein Fehler, keine Exception.
   - Ein Ziel ohne gültiges `horizon` zählt für keinen Mindestanteil, kann aber in Schritt 4 gezogen werden. Ein Config-Test stellt sicher, dass jedes Ziel im ausgelieferten Pool ein gültiges `horizon` hat.
   - Lässt die Kombo-Regel keine drei Ziele zu, gilt der bestehende Fallback (mit Nicht-Wirtschafts-Zielen auffüllen). Zwei Wirtschaftsziele gibt es nie.
   - Hat der Pool weniger als drei Ziele, werden so viele gezogen, wie da sind (heutiges Verhalten).
7. **Determinismus:** Die Ziehung hängt nur an `runs.rng_seed` und an der Config (Pool, `horizon`, `category`). Sie hängt nicht an Eröffnung, Koloniezustand oder Sol. Fehlt der Seed, bleibt der heutige Notfall (`random_int`).

Folge (gerechnet, 4 mittelfristige und 4 langfristige Ziele): Jede Ziehung hat entweder 2 mittelfristige + 1 langfristiges oder 1 mittelfristiges + 2 langfristige, beide Formen etwa gleich oft.

### 2.3 Hinweise

- Die Ziehung bleibt für alle drei Eröffnungen eines Seeds gleich. Das ist gewollt (Vergleichbarkeit der Messungen) und hängt nicht an dieser Spec.
- **Die M4-Ziehungen der Seeds 1–8 ändern sich.** Ein Vergleich „Seed 5 vorher/nachher" vergleicht also andere Ziele. Vor dem nächsten Batch die neuen Ziehungen der Seeds 1–8 ausgeben (Messplan Schritt 0).
- Die Nexus-Leiter (`run.nexus_checkpoints`) bleibt unverändert. Sie rechnet mit Fortschritt in Prozent (`current/target`), funktioniert also mit den neuen Zählern ohne Änderung.

---

## 3. Neue Definitionen

### 3.1 Forschungsvorsprung (`task_research_lead`)

**Was zählt:** die Summe der Stufen aller Kenntnisse der Kolonie (`colony_researches.level`), gleich auf welchem Weg erreicht (Labor oder stille Forschung). Typ: Zähler (aktueller Stand gegen Zielwert). Erfüllt, sobald die Summe den Zielwert erreicht; danach bleibt das Ziel erfüllt. Keine Serie. Der Parameter `min_level` entfällt.

**Warum das eine Entscheidung erzeugt:** Breite ist je Stufe billiger als Tiefe (Stufe 1: 8 AP, Stufe 5: 21 AP, gerechnet aus `knowledge.levelup_costs`), Tiefe bringt stärkere Effekte. Das Ziel belohnt damit Breite, die Kenntnis-Effekte belohnen Tiefe: Das ist die Abwägung aus GDD §10.

**Anzeige-Text (Vorschlag für content-writer):** „Forschungsvorsprung: Stufen aller Kenntnisse zusammen mindestens :target"

**Zweitgebäude begrenzen, wie viele Kenntnisse offen sind** (belegt: `database/seeders/data/researches.php`, Feld `required_building2_*`). Ohne das Zweitgebäude wird jeder Stufenaufstieg abgelehnt; AP lassen sich trotzdem einzahlen.

| Kenntnis | braucht neben dem Labor |
|---|---|
| Konstruktion | nichts |
| Geologie | Harvester (und Labor Stufe 2) |
| Agronomie | Agrardom |
| Kartografie | Hangar |
| Verteidigung | Hangar Stufe 2 (und Labor Stufe 3) |
| Handel | Cantina |
| Gesundheit | Krankenstation |

**Wann die Bots welche Gebäude haben (gemessen M4, je ein Lauf, Sol = erster Sol mit platziertem Gebäude, Stufe 0 eingeschlossen):**

| Eröffnung (Lauf) | Labor | Hangar | Cantina | Krankenstation |
|---|---|---|---|---|
| Labor (default Seed 1) | Sol ~6 | Sol ~18 | Sol ~33 | nie |
| Hangar (default Seed 3) | Sol ~46 | Sol ~6 | Sol ~70 | nie |
| Cantina (default Seed 3) | Sol ~46 | nicht ausgelesen | Sol ~6 | nie |

In keinem M4-Lauf steht eine Krankenstation (gemessen: kein Report enthält das Gebäude). Sie braucht Werkstoffe (Config `buildings.infirmary.build_cost`). Gesundheit ist damit für die Bots nie offen.

**Offene Kenntnisse und AP bis zur Summe (gerechnet aus `knowledge.levelup_costs`, Breite zuerst; Stufe 4/5 brauchen Kommandozentrale Stufe 4/5):**

| Eröffnung | offene Kenntnisse (Annahme aus der Tabelle oben) | Forschung ab | AP für Summe 14 | AP für Summe 18 |
|---|---|---|---|---|
| Labor | 6 (alle außer Gesundheit) | Sol ~6; Hangar-/Cantina-Kenntnisse ab ~18/33 | 6 × St. 2 + 2 × St. 3 = 142 | 6 × St. 3 = 198 |
| Hangar | 5 (ohne Gesundheit; Handel erst ab Sol ~70) | Sol ~46 | 5 × St. 2 + 4 × St. 3 = 151 | 5 × St. 3 + 3 × St. 4 = 219 |
| Cantina | 4 (Konstruktion, Geologie, Agronomie, Handel) | Sol ~46 | 4 × St. 3 + 2 × St. 4 = 168 | 4 × St. 4 + 2 × St. 5 = 246 |

**Bewertung:** Mit dem Labor zuerst bleibt Summe 18 erreichbar: 198 AP über rund 70 Sole, also knapp 3 AP je Sol (Annahme zum AP-Anteil). Ohne Labor-Eröffnung beginnt die Forschung erst um Sol ~46 (gemessen, je ein Lauf), mit weniger offenen Kenntnissen. 18 hieße dort über 200 AP in rund 40 Solen, also über 5 AP je Sol nur für Forschung, dazu die Tore für Stufe 4/5. Das ist praktisch „nie" (Annahme). Es wäre dieselbe Spanne „Sol 70 oder nie", die die Bewertung (§2.2) bei Handelspartner und Expeditionen kritisiert.

**Config-Vorschlag:** Klasse mittelfristig. Der Zielwert gilt **je Eröffnung**, über dasselbe Feld wie Option P5-A (§6): `target` = **14**, `opening_target` = `['sciencelab' => 18]`.
- Labor-Eröffnung 18: gezielt Sol ~70–75 (gerechnet wie oben).
- Hangar- und Cantina-Eröffnung 14: gezielt um Sol ~80–88 (gerechnet: 151–168 AP ab Sol ~46 bei etwa 4 AP je Sol). Das bleibt ein fremdes, schweres Ziel, ist aber nicht aussichtslos.
- *gemessen (M4):* Nicht gezielt spielende Labor-Läufe erreichen Summe 11 (default Labor Seed 1, Sol 85) und 9 (default Labor Seed 5, Sol 80), beide in die Tiefe. 18 fällt also nicht nebenbei. Die Werte sind aber vor der Bot-Korrektur entstanden (unten) und vermutlich zu niedrig (Annahme), also neu zu messen.
- **Wird P5-A nicht gewählt:** einheitlich **16** als Kompromiss. Gerechnet: Labor 6 × St. 2 + 4 × St. 3 = 170 AP, gezielt Sol ~65; Hangar/Cantina rund 185–205 AP ab Sol ~46, vermutlich nicht erreichbar (Annahme). Diese Variante ist zwischen den Eröffnungen sichtbar unfair.
- *Zusatzregel erwogen und verworfen:* „nur offene Kenntnisse zählen" oder „Zielwert als Anteil der offenen Kenntnisse". Dann hinge die angezeigte Zahl vom Bau anderer Gebäude ab, das wäre schwer zu erklären. Ein Zielwert je Eröffnung steht dagegen fest auf der Aufgabenkarte.
- *Aufgeklärt (Auskunft Koordinator):* Das Ziel-Profil kam in Labor-Läufen nur auf Summe 5, weil es AP in Gesundheit ohne Krankenstation einzahlte. Der Aufstieg scheiterte 75–83 Mal je Lauf. Der Bot bietet solche Kenntnisse inzwischen nicht mehr an.
- **Transparenz:** Kenntnisse mit fehlendem Zweitgebäude sollten im Labor die Voraussetzung zeigen, bevor man AP einzahlt (ui-specialist prüfen). Sonst verlieren Menschen AP wie der Bot.

### 3.2 Expertenstab (`task_senior_advisors`)

**Was zählt:** die Summe der Ränge aller Berater, die der Kolonie gehören (`advisors.colony_id = run.colony_id`), **einschließlich** zeitweise abgezogener Berater (Nexus-Sanktion, Cooldown): Eine Sanktion soll den Fortschritt nicht sichtbar zurücksetzen. Entlassene oder ausgetauschte Berater zählen nicht mehr. Typ: Zähler. Erfüllt, sobald die Summe den Zielwert erreicht; danach bleibt es erfüllt. Keine Serie, keine Beschleunigung (Owner). Der Parameter `min_rank` entfällt.

**Entscheidungen, die übrig bleiben:** wann der dritte und vierte Berater eingestellt werden, wer gehalten wird (Austausch setzt die Dienstzeit zurück), wann die Beförderung bezahlt wird (Credits, aufschiebbar).

**Anzeige-Text (Vorschlag):** „Expertenstab: Ränge aller Berater zusammen mindestens :target"

**Config-Vorschlag:** `target` = **11** (Klasse langfristig).
- *gerechnet:* Rang 1 bei Einstellung, Rang 2 nach 15 aktiven Solen, Rang 3 nach 45 (`advisor.rank_thresholds`); höchstens 4 Berater (`advisor.max_slots`), also Summe höchstens 12. Summe 11 heißt drei Berater auf Rang 3 und einer auf Rang 2, braucht also weiter den vierten Platz.
- *gemessen (M4, focus Labor Seed 4, Dienstzeiten am Sol 62: 62/53/24/18, also Einstellungen um Sol 0/9/38/44):* daraus gerechnet Summe 10 ab Sol ~60, 11 ab Sol ~83, 12 ab Sol ~89.
- *Annahme:* Wer den dritten Berater gleich mit Kommandozentrale Stufe 3 einstellt (um Sol ~25), erreicht 11 um Sol ~70.

> ⚠️ BALANCE CONCERN: Expertenstab bleibt ein Zeitziel. Zwischen gezielt und nicht gezielt liegt nur der Einstellzeitpunkt; ein Bot, der jeden freien Platz sofort besetzt, ist „gezielt", ohne es zu wollen. Die Kalibrierregel (nicht gezielt frühestens Sol ~90) lässt sich deshalb nicht sauber einhalten. Mit 10 fällt es um Sol ~60 für alle (zu früh), mit 12 ist es das alte, kaum erreichbare Ziel. 11 ist der einzige Wert dazwischen (Owner-Frage 4).

### 3.3 Selbstversorgung (`task_self_sufficiency`)

**Was zählt (je Sol):** Die Bedingung des Sols gilt, wenn **alle drei** Punkte zutreffen:

1. **Eigene Ernte ≥ Verpflegungsbedarf.** Eigene Ernte = Organika aus dem Agrardom in diesem Sol, einschließlich Agronomie-Bonus und nach dem Abschlag für unbesetzte Arbeitsplätze. Nicht dazu zählen Handel, Missionen (auch Versorgungsfahrt), Ereignisse, Händler, Anliegen. Verpflegungsbedarf = der Organika-Bedarf der Kolonisten in diesem Sol (`ResourcesService::foodNeed()`, heute die beschäftigten Kolonisten). Missions-Proviant zählt **nicht** als Verbrauch: Er ist eine Investition wie Bauen, kein Unterhalt.
2. **Mindestgröße:** Der Verpflegungsbedarf liegt mindestens bei `min_fed`. Ohne diese Schwelle wäre das Ziel ein Selbstläufer (siehe Herleitung).
3. **Kein Hunger** in diesem Sol. Ein Hunger-Sol bricht die Serie immer.

**Vorrat spielt keine Rolle:** Ein großer Bestand hilft nicht, ein leerer Bestand schadet nicht, solange die Ernte den Bedarf deckt. Das Ziel belohnt damit Produktionsausbau und eine Kolonie, die nicht über ihre Ernte hinaus wächst, statt Horten.

Typ: Serie (aufeinanderfolgende Sole), Bestwert zählt wie bisher für die Nexus-Leiter. Die Zahlen für Ernte und Bedarf müssen dieselben sein, die der Sol-Bericht zeigt (angezeigte Zahl = gemessene Zahl).

**Regolith fällt aus dem Ziel heraus (Vorschlag, Owner-Frage 3).** *gerechnet:* Der Harvester liefert 18–34 Regolith je Sol (gemessen M4, default Labor Seed 1), die Instandhaltung liegt bei Σ `decay_rate` × `repair.regolith_per_sp`, für eine späte Kolonie etwa 7–9 je Sol (Kommandozentrale und Harvester sind von Regolith-Reparatur ausgenommen). Die Regolith-Bedingung wäre fast immer erfüllt und würde nur an Solen ohne Harvester-Förderung brechen (Verlegung, Instabilität). Das ist Zufallsrauschen, keine Entscheidung. Alternative, falls der Owner Regolith behalten will: „eigene Förderung (Harvester inkl. Geologie, Bergungsflüge) ≥ Instandhaltung", ausgewertet über ein gleitendes Fenster von wenigen Solen, damit eine Verlegung die Serie nicht bricht.

**Anzeige-Text (Vorschlag):** „Selbstversorgung: mindestens :min_fed Kolonisten aus eigener Ernte satt, :target Sole in Folge"

**Config-Vorschlag:** `min_fed` = **55**, `target` = **35** (Klasse langfristig). Parameter `regolith_min` und `organics_min` entfallen.
- *gerechnet:* Höchste eigene Ernte = Agrardom Stufe 3 (16 + 20 + 30 = 66) plus Agronomie (Summe 7) = 73 Organika je Sol. Der Agrardom hat eine Instanz. Eine Kolonie kann sich also mit höchstens etwa 73 beschäftigten Kolonisten selbst versorgen; `min_fed` 55 lässt ein Band von etwa 18.
- *gemessen (M4, default Labor Seed 1):* Ernte 66–78 ab etwa Sol 32; Bedarf 62 ab etwa Sol 41, 84 ab etwa Sol 60–64. Danach dauerhaft Defizit. Bedarf ≥ 55 und Ernte ≥ Bedarf gilt also von etwa Sol 38 bis 60: eine Serie von rund 22 Solen. *gemessen (default Hangar Seed 3):* Ernte 66–77, Bedarf 50–68 bis etwa Sol 64, dann 74–80 (Defizit, teils im Wechsel).
- Folge (Annahme): Nicht gezielt bricht die Serie, sobald die Kolonie über ihre Ernte wächst (in beiden Läufen um Sol 60–64), erreicht 35 also nicht. Gezielt (ab Sol ~40 halten, Wachstum bremsen oder Agronomie ausbauen) fällt es um Sol ~75.

**Entscheidung, die das Ziel erzeugt:** Jedes neue Gebäude und jede Stufe bringt Arbeitsplätze, also Bedarf. Selbstversorgung zieht damit direkt gegen Ingenieursleistung und gegen große Kolonien. Das passt zur Kern-Fantasie „kleine Kolonie am Leben halten".

### 3.4 Ingenieursleistung (`task_engineering_output`) — nur neu kalibrieren

Mechanik unverändert (Summe der Ausbaustufen aller Gebäude-Instanzen). Klasse langfristig.

**Config-Vorschlag:** `target` = **32** (bisher 35).
- *gemessen (M4):* focus Labor Seed 5 (Ziel gezogen) 32 bei Sol 80; default Labor Seed 5 25 bei Sol 80; default Labor Seed 1 33 bei Sol 85; laut Bewertung am Ende aller Läufe 24–33.
- Folge (Annahme): gezielt um Sol ~80, nicht gezielt in den meisten Läufen nicht vor Sol 90. Ein Ausreißer (default Labor Seed 1) liegt knapp vor Sol 90. Seit #497 baut der Bot mehr Wohnhabitate, das hebt die Summe auch ohne Ziel; deshalb nicht tiefer als 32.

### 3.5 Unverändert in diesem Schritt

Handelspartner, Expeditionen (bis zur Owner-Antwort zu §6), Kreditreserve, Kolonieblüte. Sie dienen in der nächsten Messung als **Kontrolle**: Ihre Erfüllungs-Sole sollten sich nur durch die geänderte Ziehung verschieben, nicht durch Regeländerungen.

Bekannte Abweichungen, die danach anstehen (gemessen M4, Bewertung §1.3 und §2.2):
- Kreditreserve fällt nicht gezielt um Sol 60–70, mit Cantina gezielt um Sol 39–40: als mittelfristiges Ziel zu früh.
- Kolonieblüte fällt um Sol 53–84: als langfristiges Ziel im vorderen Teil zu früh. T61 verschiebt das ohnehin, deshalb erst nach T61 neu setzen (Annahme).

---

## 4. Config-Vorschlag zusammengefasst

Nur Vorschlag, Werte wie hergeleitet. `horizon` ist neu, entfallende Parameter sind gestrichen.

```php
'task_trade_volume'        => ['category' => 'economy',     'horizon' => 'medium', 'type' => 'counter', 'target' => 8],
'task_expedition_coverage' => ['category' => 'exploration', 'horizon' => 'medium', 'type' => 'counter', 'target' => 10, 'min_difficulty' => 'normal'],
'task_research_lead'       => ['category' => 'research',    'horizon' => 'medium', 'type' => 'counter', 'target' => 14, 'opening_target' => ['sciencelab' => 18]], // ohne P5-A: 'target' => 16
'task_credit_reserve'      => ['category' => 'economy',     'horizon' => 'medium', 'type' => 'streak',  'target' => 20, 'threshold' => 6000],
'task_senior_advisors'     => ['category' => 'personal',    'horizon' => 'long',   'type' => 'counter', 'target' => 11],
'task_engineering_output'  => ['category' => 'research',    'horizon' => 'long',   'type' => 'counter', 'target' => 32],
'task_self_sufficiency'    => ['category' => 'survival',    'horizon' => 'long',   'type' => 'streak',  'target' => 35, 'min_fed' => 55],
'task_colony_prosperity'   => ['category' => 'diplomacy',   'horizon' => 'long',   'type' => 'streak',  'target' => 12, 'min_band' => 'content'],
```

Nachzuziehen: `docs/game-reference.md` §18 (Tabelle der Ziele), `lang/de|en/run.php` (Texte, content-writer), Config-Kommentare mit der Herleitung (Verweis auf diese Spec).

---

## 5. Szenarien (`game:reset-player`)

`app/Console/Commands/ResetPlayer.php` setzt im Szenario `objectives-done` Berater auf `min_rank` (Zeilen um 701–741). Nach dem Umbau gibt es `min_rank` nicht mehr: Das Szenario muss Ränge so setzen, dass die **Summe** den Zielwert erreicht (z. B. vier Berater, drei auf Rang 3, einer auf Rang 2), und den Kommentar mit der Herleitung anpassen. Szenarien, die Selbstversorgung oder Forschungsvorsprung vorbereiten, sind ebenfalls auf die neue Messart umzustellen (Kenntnis-Summe statt Kenntnisse auf Stufe 5; Agrardom Stufe 3 und passender Bedarf statt Vorräte).

---

## 6. Problem 5: frühe Erfüllung der Fokus-Ziele (Owner-Frage)

**Befund (gemessen M4, Bewertung §1.3):** Handelspartner fällt mit Cantina um Sol 30–39, Expeditionen mit Hangar beim nicht gezielt spielenden Bot um Sol 27. Ohne den passenden Pfad sind beide schwer (Handelspartner ohne Cantina: M3 0 von 8). Ursache (Annahme): vor allem das **Tempo** des passenden Pfades, weniger der Vorlauf aus Phase 1. Ein reines „ab Ziehung zählen" hätte deshalb ohnehin wenig geholfen (Bewertung Z-C).

**Owner-Bedingung:** Alles, was in Phase 1 erreicht wurde, darf zählen; in Phase 2 dürfen nicht mehr Expeditionen nötig sein, als Felder offen sind.

*Annahme zur Bedingung:* Begrenzt durch Felder sind Bergungsflüge (Funde) und Ruinen-Expeditionen. Die meisten Missionen sind wiederholbar und nur durch AP, Proviant und Verschleiß gebremst (GDD §8b „Wiederholbarkeit"). Ob das für Missionen ab Schwierigkeit „normal" reicht, ist vor jeder Option zu zählen: zählbare Missionen bis Sol 70 in Hangar-Läufen aus M4 (Messplan Schritt 0).

### Option P5-A: Zielwert hängt an der Eröffnung (empfohlen)

Neues Config-Feld je Fokus-Ziel, z. B. `opening_target => ['bar' => 14]`. Bei der Ziehung wird das Fokus-Gebäude bestimmt, das beim Phasenwechsel steht (wegen des Pfadwahl-Gates genau eines), und der Zielwert entsprechend gesetzt (`run_objectives.target_value`). Gezählt wird weiter ab Run-Start.
- Vorteil: trifft die Ursache (Tempo des eigenen Pfades); die Beschriftung zeigt die echte Zahl, weil sie heute schon aus `target_value` gebaut wird; die Auswahl der Ziele bleibt eröffnungsunabhängig und damit vergleichbar; gilt mit demselben Feld auch für Kreditreserve bei Cantina.
- Nachteil: Dieselbe Ziehung hat je Eröffnung andere Zahlen; das muss die Aufgabenkarte kurz erklären („angepasst an Ihren Schwerpunkt", Text durch content-writer). Der Vorteil „Ziel passt zum Pfad" wird kleiner, aber nicht null.
- Startwerte (Annahme, linear aus dem gemessenen Tempo hochgerechnet; vorher auf Felder prüfen): Handelspartner mit Cantina 14 (statt 8), Expeditionen mit Hangar 18 (statt 10).

### Option P5-B: Stark vorerfüllte Ziele nicht ziehen

Bei der Ziehung wird ein Zähler-Ziel übersprungen, wenn es zu diesem Zeitpunkt schon einen großen Anteil erreicht hat (z. B. die Hälfte); an seine Stelle tritt das nächste Ziel derselben Klasse.
- Vorteil: keine neuen Zahlen; für Spieler leicht erklärbar („Nexus vergibt keine halb erledigte Aufgabe").
- Nachteil: trifft nur den Vorlauf aus Phase 1, nicht das Tempo danach (Annahme: reicht nicht); die Ziehung hängt dann am Koloniezustand, dieselbe Saat ergibt je Eröffnung andere Ziele, Messungen werden schwerer vergleichbar; die passende Eröffnung verliert ihr Fokus-Ziel ganz.

### Option P5-C: Höchstens ein gezählter Fortschritt je Zeitraum

Ein Fokus-Ziel zählt höchstens eine Einheit je festem Abstand an Solen (z. B. ein Einkauf je mehreren Solen), ab Run-Start.
- Vorteil: legt einen frühesten Abschluss fest, unabhängig von der Eröffnung; Phase-1-Fortschritt zählt.
- Nachteil: wenig durchschaubar („warum zählt mein Einkauf nicht?"), widerspricht dem Grundsatz „angezeigte Zahl = wirkende Zahl"; bremst auch den Spieler, der das Ziel mit fremdem Pfad mühsam verfolgt.

Verworfen: „Aufschlag in Höhe des Vorlaufs" (Zielwert = Grundwert + Fortschritt bei der Ziehung). Das wirkt wie „zählt ab Ziehung" und verletzt dieselbe Owner-Bedingung.

**Empfehlung:** P5-A, als eigener Messschritt nach M5 (§7), zusammen mit derselben Regel für Kreditreserve bei Cantina.

---

## 7. Bot-Folgen (nur benennen, Umsetzung im Bot-Code `tests/Feature/Playtest/`)

Ohne diese Anpassungen misst der Batch einen Bot, der die neuen Ziele nicht kennt.

| Stelle | Was mitziehen muss |
|---|---|
| `Utility/Valuers.php` Forschungs-Bewertung (Ziel-Anteil, heute `w_objective / (target × min_level)` für Stufen bis `min_level`) | Jede Kenntnisstufe ist ein gleicher Anteil (`w_objective / target`); günstige Stufen (Breite) sind damit je AP mehr wert. `min_level` gibt es nicht mehr. |
| `BotStrategy.php` Selbstversorgungs-Reserve (`selfSufficiencyReserve()`, `$holdsSelfSufficiencyReserve`, Regolith-Halten) | Entfällt. Neu: Solange das Ziel offen ist, keine neuen Arbeitsplätze über die eigene Ernte hinaus (Bau, Ausbau prüfen), Agrardom Stufe 3 und Agronomie vorziehen, Bedarf über `min_fed` bringen. |
| Expertenstab im Bot (Einstellung, Austausch, Beförderung) | Freie Berater-Plätze früh besetzen, nicht austauschen, Beförderungskosten als Credit-Reserve halten, solange das Ziel offen ist. |
| `Utility/Valuers.php` Ingenieursleistung | Nur Zielwert, liest `target` aus Config; keine Logik-Änderung erwartet. |
| `Utility/BotContext.php` (Ziel-Daten) | Neue Parameter (`min_fed`, `horizon`) durchreichen, alte (`min_rank`, `min_level`, `regolith_min`, `organics_min`) entfernen. |
| Tests: `BotStrategySelfSufficiencyReserveTest.php`, `BotStrategyFocusProfileTest.php`, `Utility/ResearchSourceTest.php` | An die neuen Definitionen anpassen. |
| focus-Profil allgemein | Offene Variable aus der Bewertung: verteilt AP auf alle offenen Ziele, bringt keines konzentriert durch. Vorschlag: auf zwei Ziele konzentrieren (das am weitesten fortgeschrittene plus das passende mittelfristige). Nicht in M5 ändern, sonst misst M5 zwei Dinge. |

---

## 8. Überraschungen beim Auslesen von M4

1. **Kenntnisse blieben im Ziel-Profil stecken (gemessen, aufgeklärt):** Gesundheit stand auf Stufe 0 mit 8 angesparten AP. Ursache ist das Zweitgebäude: keine Krankenstation, also wurde der Aufstieg abgelehnt. Der Bot ist korrigiert (Koordinator). Offen bleibt die Spieler-Seite: Das Labor sollte die fehlende Voraussetzung vor dem Einzahlen zeigen. Kein Bot in M4 baut eine Krankenstation, Gesundheit ist für sie nie offen.
2. **Spätspiel-Einbruch (gemessen, default Labor Seed 1):** Ab Sol ~72 liefert der Harvester 0, ab Sol ~76 der Agrardom 0, der Verpflegungsbedarf fällt auf 0, Hunger-Serie 7 bei Sol 85, obwohl die Kolonie noch 86 Kolonisten-Plätze belegt. Unklar, ob Mess-Artefakt oder echte Abwärtsspirale (Hunger → Unterbesetzung → keine Produktion). Für Selbstversorgung und T61 vorher klären.
3. **Die eigene Ernte hat eine feste Obergrenze:** ein Agrardom, höchstens Stufe 3, plus Agronomie (gerechnet ~73). Der Bedarf überholt sie in den gemessenen Läufen um Sol 60–64 (seit #497 mehr Wohnhabitate). Hunger im Spätspiel ist damit strukturell, nicht nur Bot-Verhalten.
4. **Kreditreserve ist ebenfalls zu früh** (nicht gezielt Sol 60–70), nicht nur bei Cantina.

---

## 9. Messplan

### Schritt 0 — ohne Bot (kein Batch, keine Freigabe nötig)

- Ziehung für Seeds 1–1000 mit der neuen Regel auszählen: Anteil je Ziel, Anteil „2 mittel + 1 lang" gegenüber „1 mittel + 2 lang". *Hypothese (gerechnet):* jede Ziehung erfüllt die Regel (100 %), beide Formen je 40–60 %, jedes Ziel in 25–50 % der Ziehungen.
- Neue Ziehungen für die Seeds 1–8 ausgeben (Grundlage für die Auswertung von M5).
- Aus M4 zählen: zählbare Missionen (ab „normal", erfolgreich) bis Sol 70 in Hangar-Läufen, getrennt nach Fund-gebunden und wiederholbar (Grundlage für P5-A und die Owner-Bedingung).
- Überraschungen 1 und 2 (§8) klären.

### Schritt M5 — begründet gebündelt

**Änderung:** neue Ziehungsregel + Forschungsvorsprung, Expertenstab, Selbstversorgung neu + Ingenieursleistung 32 + die Bot-Anpassungen aus §7 (außer focus-Konzentration).

**Warum gebündelt:** Die Ziehungsregel allein würde jede Ziehung mit einem langfristigen Ziel versehen, also mit einem der heute kaputten (Expertenstab, Ingenieursleistung, Selbstversorgung). Die Siegquote würde fallen, ohne dass man etwas über die neue Regel lernt. Die vier Ziel-Änderungen lassen sich trotzdem einzeln prüfen, weil jede einen eigenen Erfüllungs-Sol hat.

**Batch:** Profile default und focus, Seeds 1–8 × Labor/Hangar/Cantina, bis Sol 100 (48 Läufe). Owner-Freigabe und Dauerschätzung vorher.

**Hypothesen (je eine Zahl, widerlegbar):**

| Prüfpunkt | Erwartung | widerlegt, wenn |
|---|---|---|
| Ziehungsregel | 100 % der Läufe haben ≥ 1 mittel- und ≥ 1 langfristiges Ziel | eine Ausnahme |
| Forschungsvorsprung (18 Labor / 14 sonst; ohne P5-A einheitlich 16) | focus Labor erfüllt im Median Sol 65–78; focus Hangar/Cantina in mindestens der Hälfte der Läufe bis Sol 95; default erfüllt vor Sol 90 in höchstens 1 von 6 Läufen | Median focus Labor > Sol 85, focus Hangar/Cantina 0 Erfüllungen, oder default ≥ 2 von 6 vor Sol 90 |
| Expertenstab (11) | Median Sol 78–90 in beiden Profilen (Differenz der Profile ≤ 5 Sole, siehe Balance Concern) | Median < Sol 75 oder > Sol 95 |
| Selbstversorgung (55/35) | focus erfüllt im Median Sol 72–88; default in höchstens 1 von 6 Läufen | focus erfüllt in weniger als der Hälfte der Läufe, oder default ≥ 2 von 6 |
| Ingenieursleistung (32) | focus Median Sol 75–88; default vor Sol 90 in höchstens 25 % der Läufe | focus Median > Sol 90, oder default > 25 % vor Sol 90 |
| Kontrollen (Handelspartner, Expeditionen, Kreditreserve, Kolonieblüte) | Erfüllungs-Sole wie M4 (±5 Sole im Median) | Verschiebung > 10 Sole |
| Siegquote | gesamt 50–70 %; **kein Seed mit 0 Siegen in 6 Läufen** | ein Seed mit 0/6, oder gesamt < 40 % |
| Sieg-Sol | Median Sol 78–92 | Median < Sol 72 |

Offene Bot-Variablen (benennen, nicht wegrechnen): Konzentration des focus-Profils; ob der Bot die neue Selbstversorgungs-Regel wirklich hält (Bauverzicht); Einstellzeitpunkt der Berater; Erkundungsanteil (bindet AP, die sonst in Forschung flössen).

### Schritt M6 — Problem 5 (nach Owner-Antwort)

Eine Änderung: die gewählte Option aus §6 (empfohlen P5-A). *Hypothese:* Handelspartner mit Cantina und Expeditionen mit Hangar fallen nicht gezielt nicht vor Sol ~80, gezielt im Median Sol 68–78.

### Danach

Kreditreserve und Kolonieblüte nachkalibrieren (je eine Messung), dann T61 Stufe 1.

---

## 10. Akzeptanzkriterien (für TDD, je Punkt ein Test)

**Ziehung (`RunProgressService::drawObjectives()`, `RunTaskCatalog`)**
1. Für jeden Seed 1–1000 mit der ausgelieferten Config: genau drei verschiedene Ziele, mindestens eines mit `horizon = medium`, mindestens eines mit `horizon = long`, höchstens eines mit `category = economy`.
2. Gleicher Seed ergibt dieselben Ziele in derselben Reihenfolge, unabhängig von Eröffnung und Koloniezustand.
3. Die gespeicherte Reihenfolge entspricht der Rangfolge `crc32(seed|task_key)`, nicht der Wahlreihenfolge.
4. Pool nur mit mittelfristigen Zielen (Test-Config): es werden trotzdem drei Ziele gezogen, ohne Fehler. Gleiches für nur langfristige.
5. Ziel ohne `horizon` in einer Test-Config: wird nie als Mindestanteil gewertet, kann aber als drittes Ziel gezogen werden.
6. Config-Test: Jedes Ziel in `run.task_pool` hat `horizon` ∈ {`medium`, `long`}.
7. Pool mit weniger als drei Zielen: so viele Ziele wie vorhanden.

**Forschungsvorsprung**
8. `current_value` = Summe aller `colony_researches.level` der Kolonie (z. B. Stufen 3, 2, 1 → 6).
9. Erfüllt in dem Sol, in dem die Summe `target` erreicht; ein durch stille Forschung erreichter Stufenaufstieg zählt mit.
10. Beschriftung zeigt `target` aus `target_value`; `min_level` wird nirgends mehr gelesen.
10a. Mit `opening_target`: Steht beim Phasenwechsel das Labor als Fokus-Gebäude, wird `target_value` = `opening_target.sciencelab`, sonst `target`. Die Auswahl der Ziele ändert sich dadurch nicht (gleicher Seed, gleiche Ziele in allen Eröffnungen).

**Expertenstab**
11. `current_value` = Summe der Ränge aller Berater mit `colony_id` des Runs (z. B. Ränge 3, 3, 2, 1 → 9).
12. Ein zeitweise abgezogener Berater (`unavailable_until_tick` in der Zukunft) zählt mit.
13. Ein entlassener Berater zählt nicht mehr; ein bereits erfülltes Ziel bleibt erfüllt.
14. `min_rank` wird nirgends mehr gelesen (Szenario `objectives-done` angepasst).

**Selbstversorgung**
15. Ernte ≥ Bedarf, Bedarf ≥ `min_fed`, kein Hunger → Serie +1.
16. Ernte < Bedarf → Serie 0, auch bei großem Organika-Vorrat.
17. Vorrat 0, aber Ernte ≥ Bedarf ≥ `min_fed` → Serie +1.
18. Bedarf < `min_fed` (bei Ernte ≥ Bedarf) → Serie 0.
19. Hunger in diesem Sol → Serie 0.
20. Organika aus Handel, Missionen, Ereignissen zählen nicht zur Ernte; Missions-Proviant zählt nicht zum Bedarf.
21. Ernte und Bedarf entsprechen den Werten, die der Sol-Bericht für denselben Sol zeigt.
22. `best_streak_value` wird weiter gepflegt; die Nexus-Leiter rechnet damit.
23. `regolith_min`, `organics_min` werden nirgends mehr gelesen.

**Ingenieursleistung**
24. Zielwert kommt aus Config (`run.tasks.task_engineering_output.target`); bestehende Tests der Summe bleiben grün.

**Allgemein**
25. Die Nexus-Leiter (`meetsProgressLadder`) wertet die neuen Zähler über `current/target` korrekt aus (je ein Test mit Forschungsvorsprung und Expertenstab bei 50 % und 75 %).
26. `docs/game-reference.md` §18 und die Lang-Texte zeigen die neuen Messarten.

---

## 11. Owner-Fragen

1. **Problem 5:** Welche Option (P5-A Zielwert je Eröffnung, P5-B vorerfüllte Ziele nicht ziehen, P5-C Zähl-Tempo begrenzen)? *Empfehlung:* P5-A, als eigener Messschritt nach M5, gleich auch für Kreditreserve bei Cantina.
2. **Klassen:** Passt die Zuordnung (Fokus-Ziele und Kreditreserve mittelfristig, Kolonie-Ziele inkl. Kolonieblüte langfristig) und die Aufteilung des Korridors (mittelfristig gezielt Sol ~70–75, langfristig ~78–85)? *Empfehlung:* Ja; jede Eröffnung hat damit genau ein eigenes mittelfristiges Ziel.
3. **Selbstversorgung:** nur Organika (eigene Ernte deckt den Bedarf einer Kolonie ab Mindestgröße), Regolith fällt heraus? *Empfehlung:* Ja; die Regolith-Bedingung wäre fast immer erfüllt und bräche nur zufällig.
4. **Expertenstab:** Rangsumme 11 akzeptieren, obwohl gezielt und nicht gezielt sich kaum unterscheiden? *Empfehlung:* Ja, als bekannte Ausnahme von der Kalibrierregel; 10 wäre zu früh, 12 ist das alte Ziel.
5. **Messung:** M5 gebündelt (Ziehung + drei Umbauten + Ingenieursleistung + Bot-Anpassungen), davor Schritt 0 ohne Bot? *Empfehlung:* Ja; die Ziehungsregel allein würde nur die kaputten Ziele häufiger ziehen.
6. **Forschungsvorsprung und Zweitgebäude (Nachtrag):** Den Zielwert je Eröffnung (18 mit Labor zuerst, 14 sonst) schon in M5 einführen, obwohl P5-A noch offen ist? *Empfehlung:* Ja. Mit 18 für alle ist das Ziel ohne Labor-Eröffnung praktisch unerreichbar (Forschung erst ab Sol ~46, 4–5 offene Kenntnisse). Das Feld ist dasselbe wie bei P5-A.
