# Tile-Panel-Titel Cleanup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ersetzt den 13-fachen `@foreach`/`x-if`/`<x-entity-chip>`-Block in der Tile-Panel-Titelzeile durch einen einzigen Alpine-Lookup, entfernt dabei den Chip-Look zugunsten von schlichtem Fett-Text.

**Architecture:** `$buildingChipData` (bereits serverseitig berechnet) wird zusätzlich als JSON (`buildingCatalog`) in `window.__colonyViewData` mitgegeben. Eine neue Alpine-Methode `buildingLabel(key)` macht daraus einen Lookup. Der Blade-Block wird auf eine Zeile reduziert.

**Tech Stack:** Laravel Blade, Alpine.js 3.

## Global Constraints
- Kein Deutsch in JS/Blade-Code-Kommentaren (Projektregel) — nur `lang/de/*.php`-Werte sind Deutsch.
- Prettier muss `public/js/colony-hexgrid.js` unverändert lassen (`npx prettier --check`).
- Scope: nur `resources/views/colony/hexview.blade.php` Zeilen 166–190. Die 3 Build-Mode-`@foreach`-Ketten (322, 346, 383) NICHT anfassen.

---

### Task 1: buildingCatalog ins Colony-View-Datenobjekt aufnehmen

**Files:**
- Modify: `resources/views/colony/hexview.blade.php:9-35` (Alpine-Datenobjekt `window.__colonyViewData`)
- Modify: `public/js/colony-hexgrid.js` (neue Methode `buildingLabel`)

**Interfaces:**
- Produces: `buildingCatalog: Record<string, {label: string}>` auf `window.__colonyViewData`; `buildingLabel(key: string): string` als Alpine-Methode.

- [ ] **Step 1: `buildingCatalog` ins JSON-Objekt aufnehmen**

In `resources/views/colony/hexview.blade.php`, im `<script>`-Block, der `window.__colonyViewData` befüllt (ab Zeile 27), eine neue Zeile ergänzen:

```php
buildingCatalog: @json($buildingChipData->map(fn ($chip) => ["label" => $chip["label"]])),
```

Platzierung: direkt nach der `buildings: @json($buildings),`-Zeile.

- [ ] **Step 2: `buildingLabel()` in colony-hexgrid.js ergänzen**

In `public/js/colony-hexgrid.js`, im zurückgegebenen Alpine-Objekt (dieselbe Methodengruppe wie `buildingForTile`), folgende Methode ergänzen:

```js
buildingLabel(key) {
    return this.buildingCatalog?.[key]?.label ?? key;
},
```

Direkt neben `buildingForTile(tile)` einfügen (gleicher Verantwortungsbereich: Gebäude-Metadaten-Lookup). `buildingCatalog` selbst muss als State-Property im `return { ... }`-Objekt existieren — als `buildingCatalog: config.buildingCatalog ?? {},` neben den anderen `config.*`-Übernahmen (analog zu `ccBuildingId: config.ccBuildingId ?? 25,`) ergänzen.

- [ ] **Step 3: Prettier-Check**

Run: `npx prettier --check public/js/colony-hexgrid.js resources/views/colony/hexview.blade.php`
Expected: beide Dateien "All matched files use Prettier code style!" — falls nicht, `npx prettier --write` beide Dateien, Blade-Datei danach ein zweites Mal (`--write` Blade braucht 2 Durchläufe, siehe `docs/code-style.md`).

- [ ] **Step 4: Commit**

```bash
git add resources/views/colony/hexview.blade.php public/js/colony-hexgrid.js
git commit -m "refactor(colony): buildingCatalog-Lookup für Titel-Zeile vorbereiten"
```

---

### Task 2: Titel-Zeile auf den Lookup umstellen, Chip-Look entfernen

**Files:**
- Modify: `resources/views/colony/hexview.blade.php:166-190`

**Interfaces:**
- Consumes: `buildingLabel(key)` aus Task 1.

- [ ] **Step 1: Block ersetzen**

Aktueller Block (Zeilen 166–190):

```blade
<div class="tile-panel-title" x-show="!harvesterMoveMode && !buildMode && selectedTile" x-cloak>
    <template x-if="selectedBuilding">
        <div class="tile-panel-title__row">
            <span class="tile-panel-title__name">
                @foreach ($buildingChipData as $key => $chip)
                    @php
                        $chipLabel = $chip["label"];
                        $chipTooltip = $chip["tooltip"];
                    @endphp
                    <template x-if="selectedBuilding.building_key === '{{ $key }}'">
                        <x-entity-chip type="building" entity-key="{{ $key }}"
                            label="{{ $chipLabel }}" :tooltip="$chipTooltip" />
                    </template>
                @endforeach
            </span>
            <span class="sidebar-level-badge" x-show="selectedBuilding.level > 0"
                x-text="selectedBuilding.max_level
                    ? `Lv. ${selectedBuilding.level} / ${selectedBuilding.max_level}`
                    : `Lv. ${selectedBuilding.level}`"></span>
        </div>
    </template>
    <template x-if="!selectedBuilding">
        <span class="tile-panel-title__name" x-text="tileHeading(selectedTile)"></span>
    </template>
</div>
```

Ersetzen durch:

```blade
<div class="tile-panel-title" x-show="!harvesterMoveMode && !buildMode && selectedTile" x-cloak>
    <template x-if="selectedBuilding">
        <div class="tile-panel-title__row">
            <span class="tile-panel-title__name" x-text="buildingLabel(selectedBuilding.building_key)"></span>
            <span class="sidebar-level-badge" x-show="selectedBuilding.level > 0"
                x-text="selectedBuilding.max_level
                    ? `Lv. ${selectedBuilding.level} / ${selectedBuilding.max_level}`
                    : `Lv. ${selectedBuilding.level}`"></span>
        </div>
    </template>
    <template x-if="!selectedBuilding">
        <span class="tile-panel-title__name" x-text="tileHeading(selectedTile)"></span>
    </template>
</div>
```

- [ ] **Step 2: Prettier-Check (Blade, 2 Durchläufe falls nötig)**

Run: `npx prettier --check resources/views/colony/hexview.blade.php`
Falls nicht konform: `npx prettier --write resources/views/colony/hexview.blade.php` zweimal ausführen.

- [ ] **Step 3: Manueller Smoke-Test**

`php artisan serve` (oder bestehenden Dev-Server nutzen), `/colony/view` öffnen, ein platziertes Gebäude anklicken (z.B. Analytik-Labor). Erwartet: Titel-Zeile zeigt Name (fett, Anthrazit, kein Icon/Pill mehr) + Level-Badge in einer Zeile. Leeres Tile anklicken: weiterhin Terrain-Name wie zuvor (unverändert, `tileHeading`-Zweig nicht angefasst).

- [ ] **Step 4: Commit**

```bash
git add resources/views/colony/hexview.blade.php
git commit -m "refactor(colony): Tile-Panel-Titel auf schlichten Text ohne Chip umstellen"
```
