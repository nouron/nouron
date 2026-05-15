# Schiffe — Visuelle Beschreibungen

**Interne Referenz für Grafik und UI.**
Stand: 2026-05-15

Dieser Katalog definiert die visuelle Richtung für alle drei Schiffstypen.
Mechanikdaten und Supply-Kosten stehen im GDD §6 und in `docs/game-reference.md`.

---

## Terminologie-Entscheidung: Drohne, nicht Sonde

`lang/de/ships.php` verwendet aktuell "Drohne". Diese Entscheidung wird beibehalten.

**Begründung:** Im Nouron-Universum ist eine "Sonde" ein passiver Messkörper, der Daten sendet und sich nicht autonom bewegt — das Wort klingt nach Weltraum-Fernerkundung und passivem Empfangen. Eine "Drohne" hingegen ist ein aktiv gesteuertes unbemanntes Fahrzeug: es fliegt, erkundet, überwacht und kann verloren gehen. Das entspricht genau der Spielmechanik: die Drohne führt Navigation-Orders aus (erkunden, messen, beobachten), verbraucht kein Supply und benötigt keinen Hangar — aber sie ist verwundbar und kann durch Gefahren verloren gehen.

Das Wort "Drohne" hat auch im Deutschen den richtigen Klang: pragmatisch, technisch, unromantisch. Kein Nexus-Vorzeigeobjekt — ein Werkzeug.

---

## Drohne (`drone`)

**Rolle:** Unbemanntes Erkundungsfahrzeug. Kein Supply-Verbrauch, kein Hangar.

**Silhouette:** Kompakte sechseckige Plattform mit vier gefalteten Rotoren-/Schubdüsen-Armen, die in der Mitte zu einem zentralen Körper zusammenlaufen. In der Frontperspektive erinnert die Form an einen Pfeil oder ein Kreuz ohne Längsbalken. Flache Bauweise, kein Cockpit.

**Material / Oberfläche:** Mattschwarze Karbonkomposit-Verkleidung mit hell-grauen Nexus-Herstellermarkierungen (alphanumerisch, klein). Sensormodul an der Unterseite: dunkles Glas, leicht reflektierend. Keine Antenne — Kommunikation integriert.

**Größenverhältnis zur Kolonie:** So groß wie ein Fahrzeug auf der Erde. Zwei Kolonisten würden es zu dritt noch auf einen Anhänger heben können. Neben einem Hangar wirkt es winzig; neben der Kommandozentrale ist es kaum sichtbar. Es braucht keinen eigenen Hangar — es wird schlicht auf einer Außenplattform oder in einem freien Korridor abgestellt.

---

## Korvette (`corvette`)

**Rolle:** Leichtes Kampfschiff für Patrouille und Kolonieverteidigung. Benötigt Hangar.

**Silhouette:** Schlanker, langgezogener Rumpf mit leichter Verjüngung nach vorne. Asymmetrisch beflügelt: eine breitere untere Triebwerksgondel, zwei kompaktere obere Stabilisierungsflossen. Keine elegante Linienführung — der Rumpf sieht aus wie er mehrfach repariert und verändert wurde. Seitliches Profil: erkennbar als Schiff, aber nicht auf den ersten Blick.

**Material / Oberfläche:** Dunkelgraue Wärme-Schutzplatten (gehämmerte Oberfläche, sichtbare Nietreihen), orangefarbene Markierungen an Wartungsklappen und Triebwerksrahmen (Nexus-Protokollfarbe). Fenster minimal — nur die Kanzel ganz vorne hat transparente Abdeckung. Waffenhalterungen seitlich, eher unscheinbar als eindrucksvoll.

**Größenverhältnis zur Kolonie:** Das größte Objekt das sich in einem Hangar unterstellen lässt. Im Hangar nimmt sie den gesamten Platz ein; draußen auf der Plattform wirkt sie kompakter als erwartet — nicht bedrohlich groß, aber eindeutig kein Frachter.

---

## Frachter (`freighter`)

**Rolle:** Transportschiff für Handelsrouten und Versorgungsläufe. Benötigt Hangar.

**Silhouette:** Breiter, flacher Rumpf — fast quadratisch im Querschnitt. Massive Ladebucht dominiert den mittleren Rumpfabschnitt, Triebwerke am Heck als zwei symmetrische Blöcke. Geringe aerodynamische Eleganz: das Schiff ist für Vakuum und niedrige Atmosphären gebaut, nicht für Atmosphären-Einflug. Auf dem Boden stehend wirkt es wie ein Schiffscontainer mit angebautem Antrieb.

**Material / Oberfläche:** Helles Alu-Beige mit sichtbarer Verwitterung — Oxidationsflecken, abgeblätterter Schutzlack an den Rändern. Nexus-Frachtkennung an der Ladebucht (große Buchstaben-Ziffern-Kombination). Ladeklappen an beiden Seiten, hydraulisch, mit gelb-schwarz gestreifter Gefahrenmarkierung.

**Größenverhältnis zur Kolonie:** Ähnlich groß wie die Korvette, aber breiter und massiver. Im Hangar nimmt er mindestens so viel Platz ein. Auf einer Außenplattform neben Koloniegebäuden wirkt er imposant — die Kolonisten sind an seinen Abmessungen gemessen kleine Figuren.

---

## Allgemeine Stilnotizen für alle Schiffe

- Kein Highgloss-Finish. Alle Schiffe wirken benutzt, gewartet, nicht neu.
- Nexus-Protokollfarben tauchen an jedem Schiff auf: Orangerot für Wartungsmarkierungen und Triebwerkskennzeichnungen. Das verbindet Schiffe visuell mit den Gebäuden der Kolonie (gleiche Protokollfarbe).
- Keine Waffensysteme die auf den ersten Blick wie Kanonen aussehen. Die Korvette ist bewaffnet, aber dezent. Wer nicht weiß wonach er schaut, sieht es nicht sofort.
- Schiffe haben keine Flaggen, keine Nationalfarben, keine Insignien. Sie gehören dem Spieler oder Nexus — nicht einem Staat.
