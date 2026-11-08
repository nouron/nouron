# Labor-Vorschau: der Techtree vor dem Analytik-Labor

> **Owner-Entscheidungen 2026-11-07 (gelten vor dem Rest dieser Spec):**
> 1. Alle Knoten und Phasen sichtbar — ja.
> 2. **Kenntnisse zeigen in der Vorschau nur den Titel**, keine Beschreibung, keine Voraussetzungsliste, keine Zahlen. Die Nexus-DB darf die Infos trotzdem enthalten. Im Detailpanel steht nur der Grund „Voraussetzung: Analytik-Labor“ (plus ggf. CC-Stufe).
> 3. Hinweisband neutral — ja.
> 4. `first_visit` erst in der Vollansicht — ja.
> 5. „5 von 7“-Ziehung der Kenntnisse: separates Design später; heute sind die Kenntnisse fest, die Vorschau zeigt alle.


Stand 2026-11-06. Owner-Entscheidung vom selben Tag. Zahlenfrei nach ADR 0004. Alles, was als Annahme markiert ist, ist keine Owner-Entscheidung.

## Ziel

Der Techtree (Menüpunkt „Labor“) ist schon vor dem Bau des Analytik-Labors lesbar. Der Spieler sieht vor der Eröffnungswahl (Labor, Hangar oder Cantina zuerst, alle gleichwertig), was das Labor freischaltet. Das Labor selbst schaltet die Funktionen frei, also das Erforschen. Heute antwortet die Seite ohne Labor mit einem Fehler (409), und der Menüpunkt ist gesperrt. Beides entfällt.

Die Vorschau ist Information, kein Anreiz. Ihr Text darf das Labor nicht empfehlen, sonst kippt die Gleichwertigkeit der drei Eröffnungen.

## 1. Was die Vorschau zeigt und was nicht

Leitidee: Die Vorschau vereinfacht die Tiefe der Angaben, nicht die Menge der Knoten. Das Gitter ist dasselbe wie in der Vollansicht (GDD §16.4: der Spieler sieht immer den gesamten Techtree).

| Element | Vorschau | Vollansicht |
|---|---|---|
| Knoten, Namen, Kategorie-Farben, Phasen-Bänder, CC-Marke | ja | ja |
| Kanten (Pfeile) | ja | ja |
| Zustand der Gebäude, Schiffe, Berater | ja, echter Stand der Kolonie | ja |
| Kurzbeschreibung je Entität (ein Satz) | ja | ja |
| Voraussetzungen als Liste | ja | ja |
| Gebäude-Detail (Instanzen, „In der Kolonie errichten“, Effekte der Stufen) | unverändert, ist schon schreibgeschützt | ja |
| Kenntnisse: Zustand | immer „gesperrt“ (Ausnahme siehe §6) | echter Zustand |
| Kenntnisse: Effekt je Stufe (aktuell und nächste) | nein | ja |
| Kenntnisse: Stufenzahl, Fortschritt, AP-Kosten, Tageslimit, AP-Vorrat | nein | ja |
| Kenntnisse: Forschungsaktion | nein | ja |

Begründung: Das Detail, das die Eröffnungswahl informiert, ist „Welche Kenntnisse gibt es, wofür sind sie gut, was hängt woran?“. Die Zahlen und Stufenkurven brauchen es nicht und würden die Vorschau überladen. Der Server liefert die nicht gezeigten Felder in der Vorschau gar nicht erst, damit sie nicht nur ausgeblendet, sondern nicht vorhanden sind (Annahme: so ist es auch leichter testbar).

Annahme: Die Gebäude-Details bleiben vollständig, weil sie ohnehin nur lesen. Das Labor-Gebäude selbst zeigt damit weiterhin, was seine Ausbaustufen freischalten.

## 2. Wie die Vorschau als Vorschau erkennbar ist

- **Hinweisband** über dem Gitter, dauerhaft, nicht wegklickbar: ein Satz, was die Ansicht ist (Vorschau), ein Satz, was fehlt (das Analytik-Labor, ein Fokus-Gebäude neben Hangar und Cantina), und ein Sprung zum Labor-Knoten im Gitter. Der Ton ist neutral, ohne Aufforderung („Du solltest …“) und ohne Rangfolge der Fokus-Gebäude.
- **Knoten der Kenntnisse** im bestehenden Stil „gesperrt“ (gedimmt, GDD §16.4), Untertitel „Labor nötig“ statt Zustandsname.
- **Detailpanel einer Kenntnis**: Beschreibung und Voraussetzungen wie sonst, darüber oder an Stelle der Forschungsaktion ein Grund-Block „Voraussetzung: Analytik-Labor“. Der Block verlinkt auf den Labor-Knoten im Gitter (Auswahl dort), nicht direkt in die Kolonie. Von dort führt der bekannte Link „In der Kolonie errichten“ weiter.
- **Fehlt dem Labor selbst noch die CC-Stufe**, zeigt der Grund-Block die Kette (Labor braucht CC Lv2), nicht nur „Labor“. Er nutzt dazu die Voraussetzungsliste des Labor-Knotens.
- Annahme: Der Einführungs-Hinweis des Techtrees (`first_visit`) wird in der Vorschau nicht verbraucht. Er erscheint beim ersten Besuch der Vollansicht, damit die Einführung dann zum Gezeigten passt.

## 3. Gesperrte Aktionen

- **Erforschen** (AP investieren, Stufe abschließen) ist ohne Labor nicht möglich. Die Oberfläche bietet den Knopf gar nicht an (kein deaktivierter Knopf, der Fehler wirft). An seiner Stelle steht der Grund-Block aus §2.
- **Seite laden** antwortet normal (200). Die Antwort trägt ein Vorschau-Kennzeichen. Es gibt keinen 409 und keinen Fehlertext mehr beim Öffnen.
- **Forschungsbefehl ohne Labor** (direkter Aufruf der Schnittstelle) bleibt serverseitig abgewiesen, mit dem Maschinen-Code `lab_required` im üblichen Fehlerbild der Befehle (422, `error` und `message`). Das ist die Absicherung, nicht der Normalweg. Annahme: Der Code-Name ist ein Vorschlag.
- Menüpunkt „Labor“ ist immer anklickbar, ohne Sperrsymbol und ohne Sperr-Tooltip.
- Bau-, Schiffs- und Berater-Links verhalten sich wie bisher.

## 4. Zustände in der Vorschau

| Entität | Zustand in der Vorschau |
|---|---|
| Gebäude | echt: gebaut, verfügbar oder gesperrt, wie in der Vollansicht |
| Schiffe, Berater | echt, wie in der Vollansicht |
| Kenntnisse | gesperrt, Grund „Analytik-Labor“; die weiteren Gebäude-Voraussetzungen stehen in der Voraussetzungsliste |
| Analytik-Labor-Knoten | echt; ist er „verfügbar“, ist das der sichtbare Anknüpfpunkt der Vorschau |

Es gibt in der Vorschau keinen eigenen vierten Zustand. „Vorschau“ ist ein Merkmal der Ansicht, nicht einer Entität. Damit bleibt das Zustandsmodell der Vollansicht unverändert (gesperrt, verfügbar, gebaut).

## 5. Spoiler

Annahme (Empfehlung): Es wird nichts ausgeblendet. Phase-3-Inhalte (Leitstelle, Schiffe, Kartografie, Verteidigung) und die Bänder 4 und 5 sind in der Vorschau so sichtbar wie in der Vollansicht. Gründe:

- GDD §16.4 legt fest, dass der Techtree nie verborgen wird; eine Sonderregel nur für die Vorschau wäre ein zweiter Rechenweg für dieselbe Frage.
- Der Techtree zeigt Struktur und Namen, keine Zahlen, keine Ereignisse und keine Lore. Es gibt dort nichts zu spoilern, was nicht ohnehin im Gitter steht.
- Wer die Eröffnung wählt, sollte wissen, dass Hangar-Inhalte (Schiffe, Kartografie) im selben Gitter stehen. Das stützt die Gleichwertigkeit der Eröffnungen.

Offen als Variable: Pro Run soll nur eine gezogene Teilmenge der Kenntnisse verfügbar sein (GDD §10, §15). Falls das umgesetzt ist, zeigt die Vorschau nur die gezogenen Kenntnisse, sonst verspricht sie dem Spieler Dinge, die der Run nicht bietet. Im Techtree-Code ist heute keine Ziehung zu finden (Annahme: noch nicht umgesetzt).

## 6. Randfälle

- **Labor wird gebaut, während der Techtree offen ist** (oder zwischen zwei Besuchen). Als gebaut gilt das Labor ab Stufe 1; eine Baustelle auf Stufe 0 zählt nicht. Der Wechsel auf die Vollansicht geschieht ohne Neuladen der Seite: Die Seite reagiert auf den Wechsel des Labor-Zustands im Kopfbereich (Shell) und lädt die Techtree-Daten neu, Auswahl und Zoom bleiben erhalten (Annahme: gleiche Auswahl bleibt offen, das Panel zeigt dann die Vollansicht). Beim erneuten Betreten der Seite wird ohnehin frisch geladen.
- **Labor wird zurückgebaut auf Stufe 0** (GDD §11.5). Die Ansicht fällt auf die Vorschau zurück. Bestandsschutz: bereits erreichte Kenntnis-Stufen bleiben bestehen und werden in der Vorschau als erreicht mit ihrer Stufe angezeigt; neue Investitionen sind gesperrt (Ausnahme zu §4: erreichte Kenntnisse sind nicht „gesperrt“, sondern „gebaut“).
- **Mehrere Tabs/Fenster**: Die Vorschau-Daten sind ein Abbild zum Ladezeitpunkt; der Server prüft beim Forschungsbefehl immer neu (§3).
- **Erster Run-Start, CC Lv1**: Die Vorschau muss auch dann vollständig lesbar sein; das Labor ist dann „gesperrt“ wegen der CC-Stufe (siehe §2).
- **Kein Run aktiv / keine Kolonie**: unverändert (kein neuer Fall).

## Akzeptanzkriterien (testbar)

1. Ohne Analytik-Labor (Stufe 0 oder nicht vorhanden) beantwortet die Techtree-Daten-Schnittstelle mit 200, nicht mit 409; die Antwort enthält ein Vorschau-Kennzeichen (`preview = true`).
2. Mit Labor ab Stufe 1 ist das Kennzeichen `false` und die Antwort enthält die bisherigen Felder unverändert.
3. Die Vorschau-Antwort enthält dieselbe Menge an Knoten (Gebäude, Kenntnisse, Schiffe, Berater) und dieselben Kanten wie die Vollansicht derselben Kolonie.
4. In der Vorschau haben alle Kenntnisse ohne erreichte Stufe den Zustand `locked` und einen Sperrgrund `sciencelab`.
5. In der Vorschau fehlen für Kenntnisse die Felder Effekt der aktuellen Stufe, Effekt der nächsten Stufe, Stufenzahl-Deckel, AP-Kosten, AP-Fortschritt, Tageslimit und AP-Vorrat (bzw. sind leer/null).
6. Gebäude, Schiffe und Berater tragen in der Vorschau denselben Zustand und dieselben Detailfelder wie in der Vollansicht.
7. Ein Forschungsbefehl (Investieren) ohne Labor liefert 422 mit `error = lab_required` und einer übersetzten `message`; es ändern sich weder AP noch Kenntnis-Stufe.
8. Der Menüpunkt „Labor“ ist ohne Labor anklickbar (Link, kein Sperrzustand, kein Sperr-Tooltip) und führt auf die Techtree-Seite.
9. Die Techtree-Seite zeigt ohne Labor ein Hinweisband mit dem Vorschau-Text und einem Sprung zum Labor-Knoten; mit Labor ist das Band nicht vorhanden.
10. Das Hinweisband enthält keine Aufforderung und erwähnt Hangar und Cantina nicht wertend (Textprüfung in der Sprachdatei).
11. Wird im Detailpanel einer Kenntnis ohne Labor geöffnet, steht dort der Grund „Voraussetzung: Analytik-Labor“ und kein Investieren-Knopf; mit Labor erscheint der Knopf wie bisher.
12. Fehlt dem Labor die CC-Stufe, nennt der Grund-Block auch diese Voraussetzung.
13. Wird das Labor auf Stufe 1 gebaut, wechselt die geöffnete Techtree-Seite ohne manuelles Neuladen auf die Vollansicht (Band weg, Kenntnisse mit echtem Zustand, Knopf vorhanden); eine Baustelle auf Stufe 0 löst den Wechsel nicht aus.
14. Wird das Labor auf Stufe 0 zurückgebaut, fällt die Ansicht auf die Vorschau zurück; bereits erreichte Kenntnis-Stufen bleiben sichtbar und erhalten.
15. Die Einführungsmeldung des Techtrees (`first_visit`) wird in der Vorschau nicht als gesehen markiert (Annahme).
16. Der bisherige Test, der 409 `techtree_locked` erwartet, ist durch Test 1 ersetzt; der Sprachschlüssel `nav_techtree_locked` ist entweder entfernt oder für den Grund-Block neu belegt.

## Betroffene Stellen (Hinweis für die Umsetzung, kein Code)

- Techtree-Controller (Daten-Endpunkt, Aufbau der Antwort), Forschungsdienst (Sperrgrund), `AppHeader` (Sperrmarke entfällt), Techtree-Seite (Band, Grund-Block, Neuladen bei Labor-Wechsel), Sprachdateien de und en, `tests/Feature/Api/TechtreeApiTest.php` (409-Test).
- Szenarien in `ResetPlayer` sind nicht betroffen (keine Kosten- oder Gate-Änderung).

## Owner-Fragen (mit Empfehlung)

1. Alle Knoten und Bänder in der Vorschau zeigen, auch Phase 3 bis 5? Empfehlung: ja (§5), gleiche Linie wie §16.4.
2. Kenntnisse in der Vorschau mit Beschreibung, aber ohne Stufen-Effekte und Zahlen? Empfehlung: ja (§1); Alternative wäre „Name und Kategorie“ allein, das beantwortet „Was schaltet das Labor frei?“ aber kaum.
3. Neutraler Ton im Hinweisband, ohne Empfehlung für das Labor? Empfehlung: ja, wegen der gleichwertigen Eröffnungen.
4. Einführungs-Hinweis des Techtrees erst bei der Vollansicht? Empfehlung: ja.
5. Zeigt die Vorschau nach der Ziehung der Kenntnisse (falls umgesetzt) nur die gezogenen? Empfehlung: ja, sobald die Ziehung existiert.
