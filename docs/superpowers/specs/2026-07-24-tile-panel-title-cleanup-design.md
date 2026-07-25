# Tile-Panel-Titel: Code- und Optik-Cleanup

## Kontext
`resources/views/colony/hexview.blade.php`, Zeilen 166–190 (`.tile-panel-title`): Gebäudename wird über einen `@foreach ($buildingChipData as $key => $chip)` mit 13 verschachtelten `<template x-if="selectedBuilding.building_key === '{{ $key }}'">`-Blöcken gerendert, die je einen `<x-entity-chip>` (Icon + Pill + Hover-Tooltip) erzeugen — nur um exakt einen davon anzuzeigen. Owner empfindet das als unnötig kompliziert und will optisch schlicht Name + Level in einer Zeile, ohne Chip-Look.

## Entscheidung
- `$buildingChipData` (Label pro `building_key`, in `hexview.blade.php:13` bereits serverseitig berechnet) zusätzlich als JSON in `window.__colonyViewData` mitgeben (`buildingCatalog`).
- Neue Methode `buildingLabel(key)` in `colony-hexgrid.js`: `this.buildingCatalog[key]?.label ?? key`.
- Der 13-fache `@foreach`/`x-if`/`<x-entity-chip>`-Block in der Titel-Zeile wird durch eine Zeile ersetzt: `<span class="tile-panel-title__name" x-text="buildingLabel(selectedBuilding.building_key)"></span>`.
- Optik: kein Icon/Pill mehr, reiner Text — `.tile-panel-title__name` liefert das bereits (700 Weight, Anthrazit, keine neue CSS nötig). Level-Badge bleibt unverändert daneben (`.tile-panel-title__row` ist schon flex, also automatisch eine Zeile).

## Scope
Nur die Titel-Zeile (Zeilen 166–190). Die 3 weiteren `@foreach`-Ketten im Build-Mode (Zeilen 322, 346, 383 — Build-Katalog-Liste, In-Progress-Liste, Pending-Building-Preview) bleiben unangetastet — anderer UI-Kontext (Katalogliste mit Chip+Tooltip sinnvoll), nicht Teil dieses Fixes.

## Test
Kein PHPUnit-Test nötig (reine Blade/JS-Änderung, kein Backend-Verhalten). Manueller Smoke-Test im Browser: Gebäude auf der Kolonie-Ansicht auswählen, Titel-Zeile zeigt Name + Level ohne Chip-Pill/Icon.
