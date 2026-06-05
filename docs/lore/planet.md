# Planetensteckbrief — Zone Ypsilon-7

**Interne Referenz für Grafik, UI-Design und Content.**
Stand: 2026-05-15

---

## Grunddaten

| Merkmal | Wert |
|---------|------|
| Planetenname (generisch) | Designationsformat „YP-7 [Kennziffer]", z.B. YP-7 Kira, YP-7 Dross |
| Sektor | Zone Ypsilon-7 — ehemaliges Nouronen-System |
| Nexus-Klassifikation | Erschließungsgebiet mittlerer Priorität |
| Kolonisierungsgrund | Rohstoffvorkommen (Regolith, Mineralien); Proximity zu Nouronen-Strukturen |

**Namensprinzip:** Planeten haben keine romantischen Eigennamen. Nexus vergibt kurze alphanumerische Kennziffern. Kolonisten geben dem Planeten intern einen Spitznamen — der offizielle Designation bleibt auf Papier. Im Spiel sichtbar: die Spielerkolonie hat einen selbstgewählten Namen (z.B. "Springfield"), der Planet bleibt "YP-7 Kira" o.ä.

---

## Planetentypen (5 Varianten im ersten Release)

Jeder Run startet auf einem von fünf Planetentypen. Die Lore bleibt dieselbe — das Erschließungsgebiet Zone Ypsilon-7 umfasst verschiedenartige Planeten. Was sich ändert: Atmosphäre, Oberfläche, Licht und Farbpalette.

---

### Typ 1: Gestein (rocky)

**Kurzcharakter:** Felsiger Standardplanet. Kalt, aber nicht extrem. Regolith-reich. Der zuverlässigste Startplanet.

**Atmosphäre:** Dünn, atembar mit Ausrüstung. Stickstoff-CO2-Mix mit Spurenmengen Sauerstoff. Kein nennenswerter Wettereffekt, gelegentlich Staubwirbel. Luftdruck: 0,4 Erdatmosphären.

**Oberfläche:** Graubraunes Basaltgestein, zerklüftete Hochflächen, vereinzelt tiefer eingeschnittene Schluchten. Keine flüssigen Gewässer. Großflächige Regolith-Ablagerungen in Mulden.

**Schwerkraft:** Ca. 0,75 g — spürbar leichter als auf der Erde, kein physisches Problem für Kolonisten.

**Sterntyp:** Roter Zwerg (Klasse M). Licht orangerot getönt, niedriger Sonnenstand auch in Tagesmitte. Lange Schatten. Keine grellen Kontraste — eher diffuses, warmes Licht das nie richtig hell wird.

**Tageszeit-Stimmung:** Tagesseite: mattes Orangerot mit bräunlichem Horizont. Dämmerung: lange blaue Übergangszone bevor es vollständig dunkel wird. Nacht: dicht bestirnter Himmel (keine Mondlichtstörung).

**Kolonieoberfläche — visuelle Leitfarben:**

| Element | Farbe | Hex |
|---------|-------|-----|
| Boden / Basalt | Dunkelgraubraun | `#3d3028` |
| Regolith-Ablagerungen | Ockerbraun | `#a07848` |
| Himmel (Tag) | Blass-Orange bis Rostrot | `#c8703a` |
| Horizont-Dunst | Braun-Gelb | `#b8904a` |
| Koloniegebäude (Base-Tint) | Warm-Weiß / Alu | `#e8e0d4` |
| Koloniebeleuchtung (Nacht) | Warmes Gelb | `#f5c060` |

**Warum Menschen hier sind:** Relativ stabile Geologie, vorhersehbare Topographie, gute Regolith-Dichte. Für Nexus ist das ausreichend — der Sektor soll erschlossen werden, dieser Planet macht es leicht genug.

---

### Typ 2: Wüste (desert)

**Kurzcharakter:** Heiß, staubig, ressourcenarm. Wenige reiche Vorkommen. Hohe Tagestemperaturen, kühle Nächte. Anspruchsvoller als Gestein.

**Atmosphäre:** Sehr dünn, trocken, hoher Staubgehalt. Sandsturmsaisons können Solarernte reduzieren (Spiel-Mechanik: Hazard-Dichte). Kein Wasser an der Oberfläche.

**Oberfläche:** Weite flache Ebenen mit Sandakkumulation, gelegentlich freigelegte Felsrippen aus dem Untergrund. Dünenformationen im Windschatten von Klippen. Wenige Regolith-Stellen — was vorhanden ist, liegt tief.

**Schwerkraft:** Ca. 0,65 g.

**Sterntyp:** Gelb-weißer Zwerg (Klasse F). Intensiver, härterer Lichteinfall als auf der Erde. Kein schützendes Magnetfeld des Planeten — UV-Strahlung ist ein praktisches Problem.

**Tageszeit-Stimmung:** Tagesseite: grell-weißes Zenit-Licht, kaum Schatten in Mittagsstellung. Sandfarbener Himmel. Abendhimmel: kurze orange-rote Phase dann schnell dunkel. Nacht: schwarz, wolkenlos, intensives Sternbild.

**Kolonieoberfläche — visuelle Leitfarben:**

| Element | Farbe | Hex |
|---------|-------|-----|
| Boden / Sand | Sandgelb-Ockerbraun | `#c8a060` |
| Felsrippen | Blassrot, entfärbt | `#9a6848` |
| Himmel (Tag) | Blass-Beige bis Elfenbein | `#e8d8a8` |
| Schatten | Tiefes Warmbraun | `#604030` |
| Koloniegebäude (Base-Tint) | Elfenbein-Weiß | `#f0e8d0` |
| Koloniebeleuchtung (Nacht) | Kaltes Weiß (LED) | `#d0e8f8` |

**Warum Menschen hier sind:** Tiefenvorkommen. Nexus-Scan-Daten zeigen mineralreiche Untergrundschichten — an der Oberfläche sieht es mager aus, aber der Harvester findet, was er braucht.

---

### Typ 3: Eis (ice)

**Kurzcharakter:** Tiefgekühlt, aber strukturell interessant. Konservierte Materialien in Eisschichten, gelegentlich Nouronen-Reste. Mittlere Schwierigkeit.

**Atmosphäre:** Kaum vorhanden. Dünne Schicht aus CO2 und Stickstoff, bei starker Kälte teilweise auf der Oberfläche ausgefroren. Kein Wind in nennenswerter Stärke.

**Oberfläche:** Weitgehend von Permafrost bedeckt. Eisebenen mit eingefrorenen Gesteinsformationen. Tiefe Krater mit Schatteneis. Gelegentlich freigelegte dunkle Basaltflächen an sonnenzugewandten Hängen.

**Schwerkraft:** Ca. 0,55 g.

**Sterntyp:** Schwacher roter Zwerg (Klasse M, kleiner als beim Gesteinsplaneten). Licht sehr schwach und rötlich, beinahe mondartig tagsüber. Lange Nächte.

**Tageszeit-Stimmung:** Tagesseite: stahlblauer Himmel, blendend weißes Eis, starke Kontraste zwischen beleuchtetem Eis und hartem Schatten. Abenddämmerung: violett-blaue Töne. Nacht: nahezu absolute Finsternis mit starkem Sternhintergrund, Aurora-Phänomene möglich.

**Kolonieoberfläche — visuelle Leitfarben:**

| Element | Farbe | Hex |
|---------|-------|-----|
| Eis (flach, beleuchtet) | Blauweißes Grau | `#c8d8e8` |
| Schatten / Tiefschnee | Dunkelblau-Grau | `#304858` |
| Freigelegter Basalt | Fast-Schwarz mit Braunstich | `#282018` |
| Himmel (Tag) | Kalt-Stahlblau | `#6890b8` |
| Koloniegebäude (Base-Tint) | Reinweiß, metallisch | `#f0f4f8` |
| Koloniebeleuchtung (Nacht) | Orange-Warmweiß | `#f0a840` |

**Warum Menschen hier sind:** Konservierte Nouronen-Strukturen in Permafrost. Nexus-Klassifikation enthält den Vermerk "archäologisches Potential Klasse 2" — was das bedeutet, steht nicht dabei.

---

### Typ 4: Ozean (ocean)

**Kurzcharakter:** Nicht der Typ Ozean den man sich vorstellt. Große flache Meere mit felsigen Inseln und Küstenplateau. Hohes Hazard-Potential durch Gezeiten und Stürme.

**Atmosphäre:** Dicker als bei anderen Typen, höherer Druck. Feuchtigkeit, Wolkendecke. Windstark. Atem mit Filtermaske — Stickstoff-Sauerstoff-Mischung, aber toxische Spurengase.

**Oberfläche:** Flache Inseln und küstennahe Plateaus, von flachem Salzwasser umgeben. Keine tiefen Ozeane — eher Wattenlandschaft mit Brackwasser. Schilfartige Mineralstrukturen an Küstenlinien. Organik-Potential durch primitive Chemosynthese.

**Schwerkraft:** Ca. 0,9 g — dem Erdstandard am nächsten von allen Typen.

**Sterntyp:** Gelber Zwerg (Klasse G, erdähnlich). Licht vertraut-menschlich, aber durch dicke Wolkendecke oft gedämpft.

**Tageszeit-Stimmung:** Tagesseite: Bewölkt, diffuses weißes Licht. Wenn die Sonne durchbricht: intensive Reflexion auf dem Wasser. Dunkelgrüne bis blaugrüne Töne dominieren. Stürme: sichtbar als dunkle Wolkenwalzen am Horizont.

**Kolonieoberfläche — visuelle Leitfarben:**

| Element | Farbe | Hex |
|---------|-------|-----|
| Küstenplateau / Fels | Graugrün, verwittert | `#607860` |
| Flachwasser | Grau-Türkis | `#608090` |
| Bewölkter Himmel | Blasses Grau-Weiß | `#c0c8c8` |
| Mineralstrukturen | Gelblichgrün | `#909848` |
| Koloniegebäude (Base-Tint) | Helles Grau | `#e0e4e0` |
| Koloniebeleuchtung (Nacht) | Warmgelb (Sturmsignal) | `#e8b840` |

**Warum Menschen hier sind:** Küstennähe und Erdähnlichkeit machen Basisaufbau strukturell leichter. Nexus vermutet Rohstoffablagerungen in Küstenformationen. Die hohe Hazard-Dichte ist in der Direktive nicht erwähnt.

---

### Typ 5: Vulkan (volcanic)

**Kurzcharakter:** Aktive Geologie. Häufige Erdbeben, Lavagebiete, hohe impassable-Tile-Dichte. Schwerer Run, aber seltene Mineralien in Lavakanälen.

**Atmosphäre:** Schweflige Gase, Aschepartikel, toxisch ohne vollständigen Schutzanzug. Dicker als auf Gesteinsplaneten, aber unatembar. Gelegentliche Aschefälle.

**Oberfläche:** Erkaltete Lavaplateaus (schwarz, glatt bis aufgebrochen), aktive Ausflusskanäle (orangerot glühend in der Tiefe), Geysirfelder, erkaltete Vulkankegel als Orientierungspunkte. Regolith zwischen Lavabrocken.

**Schwerkraft:** Ca. 0,85 g.

**Sterntyp:** Roter Zwerg, nah (Klasse M, schwerer Typ). Licht gedämpft, aber Eigenleuchten der Lavafelder dominiert die Farbstimmung auf der Oberfläche.

**Tageszeit-Stimmung:** Tagesseite: Dunkelgrauer bis rotbrauner Himmel, Aschewolken als diffuse Schicht. Das Sonnenlicht kämpft gegen den Dunst an. Nacht: Lavafelder und Geysire beleuchten die Umgebung mit orangerot flackerndem Licht. Kein klarer Sternhimmel — zu viel Partikel in der Atmosphäre.

**Kolonieoberfläche — visuelle Leitfarben:**

| Element | Farbe | Hex |
|---------|-------|-----|
| Erkaltete Lava | Fast-Schwarz, leichter Rotbraun-Stich | `#201810` |
| Aktive Lavaflächen | Tiefes Orange bis Rot | `#c84820` |
| Ascheboden | Dunkelgrau | `#383028` |
| Himmel (Tag) | Dunkel-Umbra, Rot-Stich | `#584038` |
| Koloniegebäude (Base-Tint) | Helles Grau (Kontrast zur Dunkelheit) | `#d8d0c8` |
| Koloniebeleuchtung (Nacht) | Kühlblau (Kontrast zum Lava-Rot) | `#6090c8` |

**Warum Menschen hier sind:** Thermische Auslässe und Lavakanäle enthalten seltene Mineralien, die kein anderer Planetentyp bietet. Nexus-Direktive: "Wirtschaftliche Erschließung mit erhöhtem Schutzaufwand vertretbar."

---

## Koloniearchitektur — Entscheidung

**Stil: Provisorisch-modular, nicht etabliert.**

Die Kolonie sieht aus wie etwas, das man hingestellt hat und noch nicht abgebaut hat — aber länger als geplant steht. Keine eleganten Kuppelstädte. Keine architektonische Identität. Stattdessen:

- Standardmodule (Nexus-Fertigbauteile), verbunden durch Übergangstunnel und Außenkorridor-Stege
- Sichtbare Flickerei: Dichtbänder, Schutzplatten, Kabelführungen an Außenwänden
- Funktionsorientiert: Beschriftungen auf Modulen sind in alphanumerischem Nexus-Code, handschriftliche Anmerkungen von Kolonisten drüber
- Keine Einheitlichkeit über Gebäudetypen hinaus — jede Einheit sieht leicht anders aus je nach Bauepoche des Runs

**Materialsprache:** Aluminiumlegierung (hell, verwitternd zu stumpfem Grau), schwarze Dichtprofile, leuchtend orangerote Markierungen an Notfallumleitungen und Druckleitungen. Das Rot ist nicht dekorativ — es ist Protokoll.

**Größenverhältnis:** Ein vollständig ausgebauter Hangar ist das größte Gebäude der Kolonie. Die Kommandozentrale ist überraschend klein — ein Kommunikationsknoten, kein Palast. Wohnhabitate sind eng.

---

## Verwendungshinweise

- **Tile-Grafiken:** Die Farbpaletten je Planetentyp sind bindend für Tile-Design. Hintergrundtextur variiert, Kerntöne bleiben konstant.
- **Gebäude-Grafiken:** Architekturstil (provisorisch-modular) gilt für alle Planetentypen. Nur Umgebungs-Tint ändert sich.
- **UI-Farbschema:** Kolonieoberfläche nutzt die Planetentyp-Palette als Untertonschicht. UI-Elemente bleiben im definierten Weiß/Anthrazit/Rot-Schema der App.
- **Drohnen/Schiffe:** Silhouetten immer vor Planetenhintergrund — Helligkeitskontrast sicherstellen.
- **Asset-Format:** Alle gelieferten Grafiken (Tiles, Gebäude, Icons) sind **WebP mit transparentem Hintergrund, 2× Zielgröße** (HiDPI-ready). Beispiel: Tile-Textur Zielgröße 96×96 px → Datei 192×192 px. Vollständige Formatvorgaben: `docs/adr/0001-graphics-asset-format.md`.
