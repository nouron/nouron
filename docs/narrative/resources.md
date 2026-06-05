# Ressourcen-Narrativ — Interne Referenz

Stand: 2026-04-15

Dieses Dokument hält die narrativen Design-Entscheidungen zu den Spielressourcen fest.
Es dient als Referenz für Tooltips, Gebäudebeschreibungen, Event-Texte und Almanach-Einträge.

---

## Regolith (Rg)

Mondgestein, Mineralstaub, Silikate — roh, unverfeinert, direkt vor Ort verwertbar.
Auf allen Gesteinsplaneten und -monden vorhanden.

- Produktion: Harvester (lokal)
- Verwendung: Rohbaukosten für Gebäude
- Startwert: 200 Rg (Frontier-Depot)

**Narrativer Kontext:** Vor Ankunft des Spielers haben automatisierte Vorausmaschinen bereits einen Grundstock bereitgestellt. Der Spieler übernimmt eine vorbereitete, aber heruntergekommene Basis — kein leeres Stück Fels.

---

## Werkstoffe (Co)

Raffinierte Metalle, Legierungen, technische Komponenten — industriell veredelt, nicht lokal herstellbar.

- Produktion: NICHT vor Ort möglich (keine Raffinerie, keine Schwerindustrie)
- Quellen: KI-Händlerkonvois, Spieler-zu-Spieler-Handel, Events (gestrandete Frachter, Trümmerfelder)
- Verwendung: Schiffbau, High-Tech-Gebäude, Reparaturen

**Narrativer Kontext:** Eine Frontier-Kolonie kann Steine schürfen, aber keine Titanlegierungen schmelzen. Werkstoffe sind das sichtbare Zeichen externer Abhängigkeit — wer keine Handelsroute hat, sitzt irgendwann fest.

---

## Organika (Or)

Nahrung, Medizin, Biodünger, organische Verbindungen — alles Lebende, was die Kolonie am Laufen hält.

- Produktion: Agrardom (lokal, geschlossene Kreislaufwirtschaft unter Kuppel/Habitat)
- Verwendung: Bevölkerungswachstum, Moral

**Narrativer Kontext:** Ohne Organika verhungert die Kolonie oder verliert die Moral. Der Agrardom ist kein Luxus — er ist Lebenserhaltungssystem.

---

## Spielereinstieg / Atmosphäre

- Der Spieler übernimmt eine bereits vorbereitete Basis, nicht eine leere Fläche.
- Tonalität: Frontier-Kolonie. Kleine Gemeinschaft, harte Bedingungen, knappe Mittel.
- Scope: persönlich und überschaubar — kein galaktisches Imperium, keine Massenproduktion.
- Referenzatmosphäre: Reunion (Amiga/DOS), Imperium Galactica 2, Master of Orion.
- Spielgröße: Singleplayer Roguelike oder 2-4 Spieler, Brettspiel-Nähe (Catan/MoO-Stil).

---

## Händler-Berater (advisor_trader)

Beeinflusst beide Handelskanäle der Kolonie.

- **Bar/Cantina:** Ein erfahrener Händler kennt die richtigen Gäste. Höherer Rang bedeutet bessere Einkaufspreise, häufigere Angebote und gelegentlich seltene Waren, die Fremden gar nicht erst angeboten werden.
- **Nexus-Handelsschiffe:** Gute Kontakte zu den Nexus-Koordinatoren verkürzen Lieferzeiten und verbessern Konditionen. Ab Rang 3 sinkt die Lieferzeit auf 1 Tick (statt 3).

**Narrative Logik:** Handel ist kein Marktplatz-Algorithmus, sondern ein Netz aus persönlichen Beziehungen. Wer die richtigen Leute kennt — in der Cantina und bei Nexus — kommt schneller ans Ziel und zahlt weniger.
