# Nouron Design System

Source of truth for **Nouron**, a turn-based sci-fi browser strategy game (Laravel JSON API, SvelteKit frontend with Svelte 5 and its own CSS on these tokens, MySQL 8). You manage a small colony in "Zone Ypsilon-7" — the ruins of a supernova-erased civilisation — under a distant, ambivalent overseer called Nexus. Progress advances one "Sol" (game day) at a time.

**Status (updated 2026-11-15):** the rules in this readme describe the live SvelteKit UI. The React files under `components/` and `ui_kits/` are the original Claude Design export from August 2026 (built from the former Blade views); they still show the old layout and serve as a visual reference only. Where they disagree with this readme or with `docs/frontend-conventions.md`, the readme wins.

**Sources used to build this system** (repo not guaranteed accessible to every reader — explore it yourself for more depth):
- GitHub: [`nouron/nouron`](https://github.com/nouron/nouron) (branch `master`) — Laravel API, SvelteKit app in `frontend/`, game docs.
- Original export read: `docs/design-guide.md` (removed 2026-08-01, split into this folder and `docs/frontend-conventions.md`), `docs/narrative/lore_foundation.md`, `docs/adr/0001-graphics-asset-format.md`, the former Blade views and `public/css/*.css`, `lang/en/*.php`.
- Live code today: `frontend/src/lib/components/*.svelte`, `frontend/src/lib/styles/*.css`, `frontend/src/app.css`; engineering rules (breakpoints, layout heights, sync contracts) in `docs/frontend-conventions.md`.

This is the only product surface in the repo — a single browser game (no separate marketing site or mobile app was found).

## Index

- `styles.css` — root stylesheet, `@import`s everything in `tokens/`.
- `tokens/` — `colors.css`, `typography.css`, `spacing.css`, `effects.css` (radius/shadow/motion).
- `guidelines/` — foundation specimen cards (Colors, Type, Spacing, Brand) shown in the Design System tab.
- `assets/` — copied illustration samples (buildings, advisors, characters, backgrounds) and the favicon. See ICONOGRAPHY below — no logo file exists.
- `components/` — 12 React primitives grouped by concern:
  - `components/core/` — **Button**, **Card**
  - `components/navigation/` — **Navbar**, **SubnavTabs**
  - `components/data/` — **ResourceChip**, **ResourceBar**, **APChip**, **StatusBadge**, **EntityChip**, **Table**, **ProgressBar**
  - `components/feedback/` — **Dialog** (beveled sci-fi confirm/info modal)
  - `components/forms/` — **Input**, **Select**, **Checkbox**, **Switch**, **RangeSlider**, **FormField** (label/hint/error wrapper)
- `ui_kits/colony/` — click-through recreation of the Lobby and in-run Colony screens.
- `SKILL.md` — Claude Code / Agent Skill wrapper for this system.

## Company & product context

Nouron is a solo-developed (Mario Gehnke, GPLv3 code / CC BY-NC-SA assets) browser strategy game. The player runs one colony per "Run" through a limited number of Sols, spending Action Points (AP) from one shared pool per Sol to explore, build, research and trade, while a Nexus subsidy becomes a debt that must stay under a fail threshold. The frontend moved from Blade + Alpine.js + PicoCSS to SvelteKit on 2026-11-02; none of the three remain. Binding for new screens: this folder (look and patterns) and `docs/frontend-conventions.md` (engineering).

## Component inventory note

No component library or Figma file was attached — the design-guide.md's §5 ("Komponenten") enumerates the UI's real component families (Navbar, Resource Bar, Subnav/Tabs, Cards, Buttons, Resource Pills, Tables, Status Badges/AP chips), plus two more found directly in the Blade templates (Entity Chip, Sol Button) and the segmented/linear progress patterns used for repair and Sol-run progress.

A second pass added the **Dialog** (`dialogs.css`'s `.sol-modal` — beveled corner, red accent stripe, blurred scrim, used across Advisors fire, Hangar Nexus-request/mission-dispatch, and the Sol-confirm and hex-discovery dialogs) and the **form primitives** found in the auth forms, Hangar dialogs and Settings screen: `Input` (text/number/password), `Select`, `Checkbox` (plain, login "remember me"), `Switch` (`role="switch"` pill toggle, Nexus-credit/onboarding-hints), and `RangeSlider` (Consul-AP spend slider). `FormField` (label/hint/error stack) is the one intentional addition — see below.

## Content fundamentals

**Voice:** Nouron writes like a bureaucratic field report, not a game manual. `docs/narrative/lore_foundation.md` states the intent directly: entries should read like an almanac note or case file — "Kein Pathos. Kein Held, keine Bedrohung, keine Prophezeiung" (no pathos, no hero, no threat, no prophecy) — and should leave the reader with the sense of "eine sachliche Quelle zu lesen — und trotzdem das Gefühl nicht loswerden, dass etwas nicht stimmt" (a factual source, yet unable to shake the feeling that something is off).

- **Second person, direct address.** UI copy speaks to "you": onboarding hints read like a terse advisor's note — e.g. "No Engineer on board — Construction AP is running at minimum. Fix this in the Advisor screen before another Sol slips by."
- **Nexus is deliberately bureaucratic and opaque**, never friendly. It designates the setting "Zone Ypsilon-7" and classifies it as a "medium-priority development area" — corporate, procedural language standing in for an unexplained motive.
- **No exclamation points, no hype.** Even mission failure is understated: "This colony did not survive. What remains will be noted and handed over."
- **Errors separate machine code from player text.** `error` is always a stable snake_case code (e.g. `resource_limit`); `message` is the only player-facing string. Never invert this.
- **Fixed abbreviations, never translated or spelled out:** `Cr`, `Rg`, `Co`, `Or`, `Sup`, `Tr`, `Sol`, `AP`. "Sol" bare is reserved for the day counter; any other duration needs a prefix ("Duration: X Sols").
- **"Sol", never "Tick", in player text.** `tick` is the code name only ("Sols im Dienst", "CR 10 je Sol").
- **Say it once.** A status word is dropped when the button or the lock line already says the same (no "Vacant" above a "Hire" button, no "Locked" above "Needs Command Center level 2 and Cantina."). A locked item states what it needs in one sentence.
- **No emoji.** The only decorative glyphs are Bootstrap Icons and a single warning triangle (⚠) on bypass badges.
- Primary locale is German (`lang/de`) with an English translation (`lang/en`) kept in lockstep — this design system's copy examples use the English strings.

## Visual foundations

- **Palette:** white/near-white base (`#ffffff` bg, `#f7f7f5` surface), anthracite text (`#1a1a1e` primary, `#6b6b7a` secondary), hairline borders (`#e8e8ec`). One brand accent, Nouron-red `#8c2030`, used **sparingly** — active nav states and primary actions only, never warnings or errors (those are separate semantic colors, see below). Design-guide quote: "Nouron-Rot (#8c2030) ist eine elegante Markenfarbe, kein Signal-Rot" (an elegant brand color, not a signal red).
  - **Two reds, two jobs.** Accent red `#8c2030` (`--color-accent`) is the brand: primary actions, active states, selection on the map, decorative lines. Error red `#c0392b` (`--color-danger`) is the signal: error text, failed states, and buttons that destroy something (fire an advisor, abandon a run). Always through the token, never as a hex value; a destructive button must not look like a primary one.
- **Type:** two families, strictly separated by role. **Libre Baskerville** (serif, weight 400) is reserved for H1/H2 and the "NOURON" wordmark — always uppercase, 0.45em letter-spacing, never for buttons or body copy. **system-ui** carries everything functional: H3/H4 (not uppercase), body, labels, buttons, nav. Mixing the two within one hierarchy level is explicitly disallowed.
- **Spacing:** 8px base scale (4/8/16/32/64/96px = xs/sm/md/lg/xl/2xl). Card/panel internal padding is a fixed 1.5rem.
- **Backgrounds:** flat and light by default — "hell, fokussiert, viel Luft" (bright, focused, generous whitespace). No gradients, no dark mode default (`data-theme="light"` is forced). The one deliberate exception is the Cantina/Bar screen, which uses a full-bleed illustrated background image with a ~92%-opaque white surface panel laid over it for legibility — the only screen type with photographic/illustrated atmosphere as a backdrop.
- **Animation:** short and purposeful, never ornamental; it is what makes the UI feel like a game instead of a website. Every interactive element answers the pointer (see hover/press). Screens arrive with a short rise-and-fade (`ds-page-in`, ~0.28s), cards on a screen follow one after another (45ms apart), dialogs scale in while their accent bar draws from the left. Alert/hint bars slide in (`translateY`, ~0.35s ease-out) and fade out; resource/AP chips flash briefly when a value changes; the Sol report animates counters. No bounce, no elastic easing — `--ease-out` (a fast start that settles), all sub-second. Tokens and keyframes in `tokens/effects.css`. One attention cue is allowed per screen: the End-Sol button pulses softly once all AP are spent. Reduced-motion variants are not a development focus for now (owner, 2026-11-15) — keep existing ones, do not block work on missing ones.
- **Hover/press states (micro-interactions, owner 2026-11-16: on every interactive element):** boxed buttons lift 1px with a soft accent ring on hover and sink 1px on press; nav tiles raise their icon and the active tile draws an accent line on its top edge; selectable rows and cards slide 2px right behind an accent marker on their left edge; close crosses turn a quarter. All of it lives in `frontend/src/lib/styles/controls.css` — a new control gets the behaviour without own code, and must not define a competing one. Cards that are not clickable have no hover state.
- **Borders vs. shadows:** a card uses a 1px border **or** a soft shadow (`0 1px 4px rgba(0,0,0,.06)`) — never both. Popovers/dropdowns use a slightly heavier shadow (`0 4px 12px rgba(0,0,0,.1)`).
- **Corners are cut, not rounded (game-UI layer, test branch 2026-11-16).** `corner-shape: bevel` is set globally, so every `border-radius` is a straight 45° cut and borders, shadows and focus rings follow it. Three sizes: `--bevel-sm` 5px (chips, small square buttons), `--bevel-md` 9px (buttons, cut top right + bottom left), `--bevel-lg` 20px (dialogs, same two corners). Chips are cut-corner tags (`--radius-pill` is now 5px), no longer pills. True circles (dots, AP ring, portraits) write `corner-shape: round` next to their `50%`. Browsers without `corner-shape` fall back to today's small rounded corners. The intent is Swiss-clean with a technical edge: white stays the dominant surface, no gradients, no glow, no dark panels — the sci-fi comes from shape and motion only.
- **Dialogs:** one frame for all (list in `controls.css`): white, 1px anthracite hairline, large cut top right + bottom left, a short accent bar at the top left that draws in on open, blurred scrim. No coloured title bar. The bar breaks up towards the right into four ever shorter pieces. **Actions end a dialog:** buttons stand at the bottom edge of a dialog, popup or dialog column; anything that explains a button (outcome, expiry, notes) stands above it.
- **Typed text (`Typed.svelte`):** spoken lines and short system notes (cantina dialogue, first-visit notes, discoveries) reveal character by character behind an accent block cursor, done within about 1.3 s. Never for numbers, labels or controls. The full text is in the DOM from the start, so nothing jumps.
- **Small details, used sparingly:** page titles settle in from a wider tracking; level and progress tracks fill from zero; a single scan line passes down a portrait (cantina cards and advisor cards on hover, the portrait of an opened cantina dialog and the advisor cards once on arrival). Shape and motion only, no new colours.
- **Cantina figures:** every figure in the scene is the same plain portrait card (the portrait its dialog shows; unknown guests use the stranger), no name label, no pulse ring.
- **Transparency & blur:** reserved for modal/dialog scrims (`rgba(26,26,30,.55–.78)` + `backdrop-filter: blur(2–3px)`) and the Cantina surface panel — never for everyday surfaces.
- **Imagery:** illustrated/hand-drawn WebP artwork (buildings, advisor portraits, NPC characters, starfields, cantina scenes) — no photography, no pixel art. Warm-neutral, painterly, 2× HiDPI-delivered. See `docs/adr/0001-graphics-asset-format.md` in the source repo for the full spec.
- **Layout:** slim top bar (wordmark left; right: trust, AP ring, Sol counter with the End-Sol button below it, user menu) + a hint bar below it, a resource rail as a transparent left overlay holding the materials only (expanded in the colony, collapsed to a handle elsewhere; toggles on click/tap only, never on hover; chips are subtle glass tags, opaque fallback for reduced transparency; never shifts page content) and a fixed tile navigation at the bottom of every in-run screen (groups with a sub-row that opens above the bar); content area uses generous section spacing (32px between sections). Lobby is a centered 56rem column. Colony view is a two-column grid (canvas + fixed 320px sidebar) that stacks on mobile.
- **Responsive breakpoints (fixed set, do not add more):** mobile ≤599px, tablet ≤767px, desktop ≥768px, wide ≥900px. The bottom navigation is identical on desktop and mobile (no nav-specific breakpoints).

## Interaction patterns

Rules that came out of the owner's play-tests in November 2026. They apply to every new or reworked screen.

**Header (always visible in a run)**
- Sol, trust and AP live in the header, top right — the three values a player checks before every decision. Materials stay in the left rail.
- **AP ring:** a ring with one segment per AP of the current Sol; spent segments turn grey, remaining ones stay accent red, the number in the middle is what is left. Never a bare "3 / 4" text.
- **Trust** shows its band as a word plus its AP effect ("Stabil ±0 AP"), not a number; the number appears only once the lab is built.
- **End-Sol button** sits under the Sol counter. It pulses once no AP are left; on phones it shrinks to its icon. Ending a Sol with unspent AP still asks first, because the AP are lost.
- Each header value opens a popup below it on click; Escape or a click elsewhere closes it.

**Action cards** (advisor, building, ship, cantina offer)
- **Title = what it is** (role or name, display font); the effect line sits directly under the title; lore follows in italics, clamped to a few lines.
- **The main action is one large button at the card's bottom edge**, full card width, at least 44px high (48px on phones).
- **A one-off cost is a chip inside that button** (label left, "CR 300" right), as with building. Recurring cost and gain stand as one line above it ("CR 10 je Sol · +1 AP"). AP cost follows the same chip rule.
- **No confirmation popup when the card already shows everything.** The click acts. Popups stay for actions that lose something (firing an advisor, ending a Sol with AP left, tearing down a building) — that boundary is the working rule so far, not yet an explicit owner decision.
- **Cannot afford = disabled button plus red cost chip**, no error text. Only the button face fades, the chip stays readable.
- **Rare refusals** from the server appear as a quiet grey line on the card, never as a banner or alert.
- **Locked cards** dim their content only, not the panel; the lock line stays fully readable.

**Resource rail and hints**
- The rail never pushes or resizes page content. If it covers something, collapse it, move it lower or make it more opaque.
- A hint that leads somewhere is a link and ends in an arrow ("→").

**Numbers**
- A shown number is the number that applies. Sources are listed one by one and add up to the total (popups of trust, AP, supply).

## Iconography

- **Bootstrap Icons** (`bootstrap-icons@1.11.3`, CDN) is the system's only icon set — loaded via one `<link>` in `frontend/src/app.html`, used as `<i class="bi bi-hexagon">` inline glyphs throughout the navbar, entity chips, and status indicators. No custom icon font, no SVG icon sprite.
- **SVG** is reserved for structural UI (the hex-grid canvas, clip-paths) — never for illustrated artwork.
- **No emoji, no unicode-symbol icons** except a single ⚠ on warning badges and a decorative "⬡" (hollow hex) glyph used as an empty-state placeholder.
- Illustrated game-object icons (resources, buildings, ships, advisor portraits) are hand-drawn WebP images, not part of the icon font — see `assets/` for samples copied from the source repo (`public/img/buildings`, `public/img/advisors`, `public/img/characters`).

## Logo

**No logo/brand mark exists in the source.** The wordmark is plain text — "NOURON" in Libre Baskerville, uppercase, 0.45em letter-spacing — used everywhere a mark would normally go (navbar, loading screens). Do not draw or invent a symbol; if a mark is ever supplied, add it under `assets/brand/`.

## Fonts

Libre Baskerville is loaded via the same Google Fonts CDN `@import` the source repo itself uses (`tokens/typography.css`) — not self-hosted. No substitution was needed. system-ui resolves to the OS default sans-serif and needs no font file.

## Intentional additions

- `FormField` (`components/forms/`) — not a named family, but the label+control+hint/error stack every form input in the source repeats inline (`.hangar-form label`, auth-form errors). Factored out to avoid re-implementing it per screen.

Everything else in `components/` maps to a family explicitly named in `docs/design-guide.md` §5, or found directly in the Blade templates/CSS (Entity Chip, Sol Button, progress bars, `.sol-modal` dialog shape, form field styling in `hangar.css`/auth views).

## Caveats

- The production hex-grid canvas (`colony-hexgrid.js`) is a hand-rolled SVG rendering engine with live tile state, animations and click handling; the UI kit's Colony screen approximates it with a small static hex layout for demonstration, not a port of that engine.
- In the React kit the "End Sol" trigger is a normal `Button` with a `Dialog` confirm step. The live game has its own `SolButton.svelte` (pulse at 0 AP, confirm for unspent AP, animated Sol report).
- The React kit still shows the resource bar as a row under the navbar, a top navigation with tabs, AP chips per category and a hire dialog. All four are superseded: see "Layout" and "Interaction patterns" above.
- Stylesheets in `frontend/src/lib/styles/` were swept for unused classes on 2026-11-15. Two families look unused to a text search but are built at runtime and must stay: `comm-nexus-badge--*` (server sends the class) and `sol-report__line--*` (tone appended in code).
- Advisors, Techtree, Hangar, and Cantina screens were read for tone/asset reference but not built out as full UI-kit screens (time-boxed to Lobby + Colony); their controls are still covered by the shared `components/`.

