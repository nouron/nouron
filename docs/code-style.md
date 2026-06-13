# Code-Style (Linter-Konventionen)

Vor **jedem Commit** läuft `.githooks/pre-commit`: PHP→Laravel Pint (Auto-Fix), JS/CSS→Prettier (Auto-Fix), Blade→Prettier `--check` (blockt, kein Auto-Write). Code so schreiben, dass der Linter nichts mehr ändert.

> **Wichtigste Regel (PHP + JS): NIE vertikal ausrichten.** Pint (`binary_operator_spaces`) und Prettier kollabieren ausgerichtete `=>`, `=` und Objekt-Keys auf genau ein Leerzeichen. Die Altbestand-Codebase war ausgerichtet — dieser Stil ist **veraltet**.

```php
// FALSCH (wird kollabiert)            // RICHTIG
'explore'   => 'Erkunden',             'explore' => 'Erkunden',
'deep_scan' => 'Sondieren',            'deep_scan' => 'Sondieren',
$ap      = 4;                          $ap = 4;
```

## PHP (Laravel Pint, `laravel`-Preset — `pint.json`)

- **Keine Ausrichtung** von `=>`/`=`/Operatoren — genau ein Space.
- Strings: einfache Quotes ohne Interpolation/Escapes (`'text'`); Konkatenation mit Spaces (`'a'.$b` → `'a' . $b`).
- `!` ohne Folgespace: `!$found`.
- `use`-Imports alphabetisch sortiert; **keine ungenutzten** Imports.
- Leerzeile vor `return` und vor Statements nach Blöcken.
- Leere Body einzeilig: `public function __construct() {}`.
- Trailing Comma in mehrzeiligen Arrays/Argumentlisten.
- Cast mit Space: `(int) $x`.
- Klassen-Member (const/property/method) durch je eine Leerzeile getrennt.
- Datei endet mit **genau einem** Newline.
- Test-Methoden snake_case: `public function test_does_something(): void`.
- `database/migrations/` ist von Pint **ausgenommen** (historische Dateien) — trotzdem sauber halten.

Prüfen/fixen: `bin/pint --test <pfade>` bzw. `bin/pint <pfade>`.

## JavaScript (Prettier — `.prettierrc.json`)

- 4 Spaces Indent, max. 120 Zeichen Zeilenlänge, **einfache** Quotes, Semikolons.
- Objekt-Keys/Werte **nicht** ausrichten (siehe oben).
- Keine manuelle Spalten-Ausrichtung in Multiline-Literalen.

## CSS (Prettier)

- 4 Spaces, eine Deklaration pro Zeile, Space nach `:`.
- Funktionsargumente mit Space nach Komma: `rgba(0, 0, 0, 0.1)`.
- Doppelquotes in `content`/`url(...)`.

Prüfen/fixen: `npx prettier --check <files>` bzw. `npx prettier --write <files>`.

## Blade (Prettier `--check` — NICHT auto-formatiert)

Der Hook **blockt** nicht-konforme `.blade.php`-Dateien, schreibt aber nichts (das Blade-Plugin ist auf Alpine-Templates zu aggressiv → bewusst manuell).

- Beim ersten Commit einer geänderten Blade-Datei blockt der Hook → einmalig bewusst formatieren: `npx prettier --write <datei.blade.php>`, dann committen.
- Stil: 4 Spaces; Direktiven-String-Args in Doppelquotes (`@extends("layouts.colony")`).

## Aktivierung pro Clone

```
npm install
git config core.hooksPath .githooks
```
