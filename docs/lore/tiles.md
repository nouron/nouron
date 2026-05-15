# Tile-Katalog — Visuelle Beschreibungen

**Interne Referenz für Grafik und UI.**
Stand: 2026-05-15

Dieser Katalog beschreibt das Aussehen jedes Tile-Typs. Die Mechanik der Tiles steht im GDD §4a.
Die hier festgelegten Farben sind Zielwerte für den Standardplanetentyp (rocky). Für andere Planetentypen
gilt das jeweilige Farbschema aus `docs/lore/planet.md` — die Strukturbeschreibung bleibt gleich, nur der Ton ändert sich.

**Hex-Grid-Orientierung:** Pointy-top. Tiles sind spitzzulaufend oben und unten, breit in der Mitte.

---

## Terrain-Tiles

### `terrain_empty`
**Kolonie-Zone, bebaubar**

Farbe: Warmgrau mit schwachem Braun-Unterton. Glatte, leicht aufgeraute Oberfläche — als wäre der Boden für eine Nutzung vorbereitet oder zumindest nicht mehr wild.
Innerhalb der Kolonie-Zone erhält dieses Tile einen subtilen hellen Innentint, der es von Exploration-Zone-Tiles unterscheidbar macht.

| Merkmal | Wert |
|---------|------|
| Basisfarbe | `#6a5e54` |
| Kolonie-Zone-Tint | `#7a6e64` (aufgehellt) |
| Markantes Merkmal | Leichte Texturierung, keine auffälligen Formen — der "leere Bauplatz" |

---

### `terrain_hazard`
**Gelände-Gefahr — vor Neutralisierung**

Erkennbar an einer unregelmäßigen Oberflächenstruktur: aufgebrochenes Gestein, instabiler Untergrund, scharfe Kanten. Orangegelber Warnrand (Protokollfarbe). Wirkt wie ein Tile das noch nicht angefasst wurde — und aus gutem Grund.

| Merkmal | Wert |
|---------|------|
| Basisfarbe | `#5a4838` |
| Warnrand | `#e09030` |
| Markantes Merkmal | Warnfarbe am Rand; unruhige innere Textur |

**Nach Neutralisierung:** Tile wird zu `terrain_empty`. Optionaler kurzer Übergangseffekt: der Warnrand verblasst. Das Tile behält für kurze Zeit eine leicht dunklere Tönung als seine Nachbarn ("frisch geräumt").

---

### `terrain_impassable`
**Nicht begehbar, nicht bebaubar**

Klippen, Abgründe, Lavaströme, extreme Geländeformationen — je nach Planetentyp verschieden. Das Tile ist dunkel, markant, keine Interaktion möglich. Kein Rand, kein Hinweissymbol — wer hierhin schaut, sieht sofort, dass hier nichts zu machen ist.

| Merkmal | Wert |
|---------|------|
| Basisfarbe (rocky) | `#282018` (fast-schwarz) |
| Basisfarbe (ice) | `#203040` (tiefdunkelblau) |
| Basisfarbe (volcanic) | `#100808` (tiefschwarz mit Rotschimmer) |
| Markantes Merkmal | Dunkelster Tile auf der Karte; keine UI-Interaktion möglich |

---

### `terrain_fog`
**Unerkundet — Fog of War**

Dieses Tile wurde noch nicht mit Navigation-AP aufgedeckt. Die Darstellung deutet die Form an, gibt aber keine Information über den Inhalt. Kein Lebzeichen, kein Signal — Stille.

| Merkmal | Wert |
|---------|------|
| Basisfarbe | `#2a2620` |
| Overlay | Leichte Rauchtextur oder Körnigkeit über dem Tile |
| Markantes Merkmal | Kein Symbol, kein Rand. Nur Dunkelheit mit leicht sichtbarer Tile-Kontur |

**Variante "Signal erkannt":** Wenn ein Tiefenscan-Signal vorhanden ist, aber noch nicht untersucht wurde, erscheint ein kleiner Puls-Indikator in der Tile-Mitte (ein einziger Pixel-Punkt, schwaches Blinken). Keine Farbe, kein Icon — nur Präsenz.

---

### `terrain_locked`
**Noch nicht durch CC-Expansion freigeschaltet**

Tile ist prinzipiell sichtbar (keine Fog-Decke), aber noch nicht zur Kolonie-Zone gehörig. Leicht gedämpfter Ton, kein Interaktionsrahmen. Unterschied zu Fog: man sieht es, kann es aber nicht nutzen.

| Merkmal | Wert |
|---------|------|
| Basisfarbe | Wie `terrain_empty`, aber 20% dunkler und entsättigter |
| Overlay | Leichtes Schraffur-Muster oder Vignette |
| Markantes Merkmal | Sichtbar aber inaktiv — "noch nicht erschlossen" |

---

## Ressource-Tiles

Ressource-Tiles liegen immer in der Exploration Zone. Ihre Qualität ist nach normalem Scan sichtbar, ihr Event-Overlay erst nach Tiefenscan.

### `regolith_rich`
**Reicher Erzknoten**

Heller, grob strukturierter Boden mit sichtbaren Mineraleinschlüssen. Die Oberfläche glänzt leicht im Licht — Quarzkristalle und Metallspuren im Regolith. Klar erkennbar als wertvolles Vorkommen.

| Merkmal | Wert |
|---------|------|
| Basisfarbe | `#a07848` (sattes Ockerbraun) |
| Akzent | Helle Flecken `#d0b870` — Mineralglanz |
| Qualitäts-Indikator | Drei Punkte / Balken-Symbol oben rechts im Tile |
| Markantes Merkmal | Der hellste und wärmste aller Ressource-Tiles |

---

### `regolith_normal`
**Normales Vorkommen**

Mittlerer Ockerbraun-Ton, gleichmäßige Textur ohne besondere Akzente. Solide Ressource, nichts Besonderes. Das häufigste Ressource-Tile.

| Merkmal | Wert |
|---------|------|
| Basisfarbe | `#806038` |
| Qualitäts-Indikator | Zwei Punkte / Balken-Symbol oben rechts |
| Markantes Merkmal | Unauffällig — der Standard |

---

### `regolith_poor`
**Armes Vorkommen**

Dunkler, stumpfer Ton. Schiefergrau mit schwachem Braun. Wenig zu holen, aber besser als nichts.

| Merkmal | Wert |
|---------|------|
| Basisfarbe | `#584830` (dunkles Graubraun) |
| Qualitäts-Indikator | Ein Punkt / Balken-Symbol oben rechts |
| Markantes Merkmal | Das dunkelste Ressource-Tile; wirkt ausgelaugt |

**Erschöpfungs-Counter:** Alle drei Regolith-Typen zeigen ab einem gewissen Abbaugrad einen Erschöpfungsbalken am unteren Tile-Rand. Je mehr abgebaut wurde, desto tiefer fällt der Balken — sichtbares Verschwinden der Ressource.

---

## Event-Tiles

Event-Tiles haben ein Overlay über dem Basis-Terrain. Der Untergrund (Terrain oder Regolith) bleibt erkennbar. Das Event-Overlay erscheint erst nach Tiefenscan; bis dahin zeigt das Tile nur seinen Ressourcentyp oder `terrain_empty`.

**Vor Tiefenscan:** Keine sichtbaren Hinweise außer dem optionalen Signal-Puls (siehe `terrain_fog`-Variante).

**Nach Tiefenscan:** Event-Icon und charakteristische Farbakzente erscheinen auf dem Tile.

---

### `event_wreck`
**Schiffswrack**

Verformtes, dunkles Metallgerüst ragt aus dem Boden. Erkennbare Kontur eines ehemaligen Schiffes, aber stark verwittert. Kein Licht, keine Aktivität. Neutrales Grau-Anthrazit mit Rostspuren.

Icon-Farbakzent: `#806858` (verwittertes Metall)

---

### `event_ruin`
**Ruine — alien oder alt**

Geometrische Reste im Untergrund — Mauern, Bögen, regelmäßige Strukturen die nicht natürlich entstanden sein können. Nouronen-Assoziation möglich, aber nicht bestätigt. Dunkelgrau mit bläulichem Schimmer.

Icon-Farbakzent: `#6878a0` (kühles Blaugrau — fremdartig, aber nicht bedrohlich)

---

### `event_bunker`
**Vergrabener Bunker**

Kaum sichtbar. Nur eine Lukenöffnung oder eine kantige Kante im Boden verrät die Struktur. Dunkel, ernst, funktional. Nexus-Fertigbau-Ästhetik.

Icon-Farbakzent: `#708060` (Militärgrün, verblasst)

---

### `event_probe`
**Alte Sonde / Forschungsstation**

Ein stelzenartiges Objekt auf dem Boden, leicht geneigt, eines der Beine gebrochen. Antenne verbogen, aber Korpus intakt. Könnte Nexus-Herkunft sein — oder etwas älteres.

Icon-Farbakzent: `#a09060` (Alu-Beige, verwittert)

---

### `event_crystal`
**Kristallformation**

Aufragende Mineralkristalle, transparent mit farbiger Innenwirkung — je nach Planetentyp: bläulich (Eis), gelblich-orange (Vulkan), weiß-grau (Gestein). Ungewöhnlich, nicht eindeutig verwertbar ohne Analyse.

Icon-Farbakzent: `#90c0d0` (Eis), `#d09040` (Vulkan), `#c0b8a8` (Gestein)

---

### `event_vent`
**Thermaler Auslass / Geysir**

Kleiner Dampfkegel über einer Öffnung im Boden. Periodisches Ausstoßen — sichtbar als Animation (optionale Bewegung: Dampfschwaden). Nur auf Vulkan-Planeten.

Icon-Farbakzent: `#c87050` (Warmrot-Orange)

---

### `event_cave`
**Höhleneingang**

Dunkle Öffnung im Felsboden, unbekannte Tiefe. Kein Licht aus dem Inneren. Der Rand der Öffnung wirft harten Schatten.

Icon-Farbakzent: `#404040` (fast-schwarz)

---

### `event_cache`
**Verstecktes Depot**

Halb eingegrabene Kisten oder Behälter, erkennbar als menschliches Fabrikat. Unbekanntes Alter, unbekannter Inhalt. Warum hier, wer hat sie zurückgelassen — keine Antworten.

Icon-Farbakzent: `#806040` (verwittertes Khaki)

---

### `event_signal`
**Schwaches Signal**

Keine physische Besonderheit auf dem Tile sichtbar. Nur ein kleines Antennensymbol zeigt: hier wurde etwas empfangen. Was, bleibt unklar. Das unbestimmteste und beunruhigendste aller Event-Tiles.

Icon-Farbakzent: `#60c080` (schwaches Grün — Funksignal-Konvention)

---

### `event_anomaly`
**Unerklärliche Anomalie**

Ein leerer Bereich mit schwacher Lichtverzerrung oder Bodenverfärbung die sich keinem bekannten Muster zuordnen lässt. Keine erkennbare Struktur. Messwerte weichen ab. Kein eindeutiges Risiko, keine eindeutige Chance.

Icon-Farbakzent: `#9070b0` (gedecktes Violett — unbekannt, fremd)

---

## Tile-Kompositionsregeln

1. Alle Ressource-Tiles und Event-Tiles sind erkennbar verschiedene **Schichten** auf demselben Tile-Hintergrund — nicht ersetzende Grafiken. Terrain bleibt sichtbar.
2. Das **Qualitäts-Symbol** (Punkte/Balken) sitzt oben rechts, außerhalb des zentralen Tile-Bereichs.
3. Das **Event-Icon** sitzt zentral auf dem Tile, leicht nach oben versetzt (damit Gebäude-Icons darunter platzierbar sind).
4. **Gebäude auf Tiles** überlagern den Tile-Typ — das Tile ist noch erkennbar, das Gebäude steht darauf.
5. **Harvester auf Regolith-Tiles:** Das Regolith-Tile bleibt sichtbar, der Harvester steht als Gebäude-Icon darauf. Erschöpfungsbalken bleibt sichtbar unter dem Harvester-Icon.
