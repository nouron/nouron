# Nouron Design System

Source of truth for **Nouron**, a tick-based sci-fi browser strategy game (PHP 8.2/Laravel 12, Alpine.js + PicoCSS, SQLite). You manage a small colony in "Zone Ypsilon-7" — the ruins of a supernova-erased civilisation — under a distant, ambivalent overseer called Nexus. Progress advances one "Sol" (game day/tick) at a time.

**Sources used to build this system** (repo not guaranteed accessible to every reader — explore it yourself for more depth):
- GitHub: [`nouron/nouron`](https://github.com/nouron/nouron) (branch `master`) — Laravel app, Blade views, CSS, game docs.
- Key files read: `docs/design-guide.md` (binding UI spec, May 2026), `docs/narrative/lore_foundation.md`, `docs/adr/0001-graphics-asset-format.md`, `resources/views/**/*.blade.php`, `public/css/*.css`, `lang/en/*.php`.

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

Nouron is a solo-developed (Mario Gehnke, GPLv3 code / CC BY-NC-SA assets) browser strategy game. The player runs one colony per "Run" through a limited number of Sols (ticks), spending category-specific Action Points (Navigation/Construction/Research/Economy/Strategy) to explore, build, research and trade, while a Nexus subsidy becomes a debt that must stay under a fail threshold. The game has been migrating from a legacy Bootstrap 5 + jQuery stack to Alpine.js + PicoCSS; `docs/design-guide.md` (v1.0, May 2026) is the binding spec for all new screens and is this design system's primary source.

## Component inventory note

No component library or Figma file was attached — the design-guide.md's §5 ("Komponenten") enumerates the UI's real component families (Navbar, Resource Bar, Subnav/Tabs, Cards, Buttons, Resource Pills, Tables, Status Badges/AP chips), plus two more found directly in the Blade templates (Entity Chip, Sol Button) and the segmented/linear progress patterns used for repair and Sol-run progress.

A second pass added the **Dialog** (`dialogs.css`'s `.sol-modal` — beveled corner, red accent stripe, blurred scrim, used across Advisors hire/fire, Hangar Nexus-request/mission-dispatch, and the Sol-confirm and hex-discovery dialogs) and the **form primitives** found in the auth forms, Hangar dialogs and Settings screen: `Input` (text/number/password), `Select`, `Checkbox` (plain, login "remember me"), `Switch` (`role="switch"` pill toggle, Nexus-credit/onboarding-hints), and `RangeSlider` (Consul-AP spend slider). `FormField` (label/hint/error stack) is the one intentional addition — see below.

## Content fundamentals

**Voice:** Nouron writes like a bureaucratic field report, not a game manual. `docs/narrative/lore_foundation.md` states the intent directly: entries should read like an almanac note or case file — "Kein Pathos. Kein Held, keine Bedrohung, keine Prophezeiung" (no pathos, no hero, no threat, no prophecy) — and should leave the reader with the sense of "eine sachliche Quelle zu lesen — und trotzdem das Gefühl nicht loswerden, dass etwas nicht stimmt" (a factual source, yet unable to shake the feeling that something is off).

- **Second person, direct address.** UI copy speaks to "you": onboarding hints read like a terse advisor's note — e.g. "No Engineer on board — Construction AP is running at minimum. Fix this in the Advisor screen before another Sol slips by."
- **Nexus is deliberately bureaucratic and opaque**, never friendly. It designates the setting "Zone Ypsilon-7" and classifies it as a "medium-priority development area" — corporate, procedural language standing in for an unexplained motive.
- **No exclamation points, no hype.** Even mission failure is understated: "This colony did not survive. What remains will be noted and handed over."
- **Errors separate machine code from player text.** `error` is always a stable snake_case code (e.g. `resource_limit`); `message` is the only player-facing string. Never invert this.
- **Fixed abbreviations, never translated or spelled out:** `Cr`, `Rg`, `Co`, `Or`, `Sup`, `Tr`, `Sol`, plus AP types `Nav`/`Con`/`Res`/`Eco`/`Str`. "Sol" bare is reserved for the day counter; any other duration needs a prefix ("Duration: X Sols").
- **No emoji.** The only decorative glyphs are Bootstrap Icons and a single warning triangle (⚠) on bypass badges.
- Primary locale is German (`lang/de`) with an English translation (`lang/en`) kept in lockstep — this design system's copy examples use the English strings.

## Visual foundations

- **Palette:** white/near-white base (`#ffffff` bg, `#f7f7f5` surface), anthracite text (`#1a1a1e` primary, `#6b6b7a` secondary), hairline borders (`#e8e8ec`). One brand accent, Nouron-red `#8c2030`, used **sparingly** — active nav states and primary actions only, never warnings or errors (those are separate semantic colors). Design-guide quote: "Nouron-Rot (#8c2030) ist eine elegante Markenfarbe, kein Signal-Rot" (an elegant brand color, not a signal red).
  - Note: some older CSS in the source repo (`colony.css`, `lobby.css`) still hardcodes a slightly different red (`#c0392b`, PicoCSS's `--pico-del-color`) predating the May-2026 guide. This system takes the documented `#8c2030` as canonical and flags the discrepancy — a live migration in progress on the actual product.
- **Type:** two families, strictly separated by role. **Libre Baskerville** (serif, weight 400) is reserved for H1/H2 and the "NOURON" wordmark — always uppercase, 0.45em letter-spacing, never for buttons or body copy. **system-ui** carries everything functional: H3/H4 (not uppercase), body, labels, buttons, nav. Mixing the two within one hierarchy level is explicitly disallowed.
- **Spacing:** 8px base scale (4/8/16/32/64/96px = xs/sm/md/lg/xl/2xl). Card/panel internal padding is a fixed 1.5rem.
- **Backgrounds:** flat and light by default — "hell, fokussiert, viel Luft" (bright, focused, generous whitespace). No gradients, no dark mode default (`data-theme="light"` is forced). The one deliberate exception is the Cantina/Bar screen, which uses a full-bleed illustrated background image with a ~92%-opaque white surface panel laid over it for legibility — the only screen type with photographic/illustrated atmosphere as a backdrop.
- **Animation:** minimal and functional, never decorative. Alert/hint bars slide in (`translateY`, ~0.35s ease-out) and fade out; resource/AP chips flash briefly (scale + color pulse, ~0.5–0.6s) when a value changes; the Sol-Report screen animates counters with an eased tween. No bounce, no elastic easing — everything is `ease`/`ease-out`, sub-second. Tokenized as `--duration-fast`/`--duration-base`/`--duration-slow` + keyframes `ds-slide-fade-in`, `ds-flash-pulse`, `ds-dialog-in`, `ds-scrim-in` in `tokens/effects.css` — see the Motion guideline cards.
- **Hover/press states:** buttons darken slightly on hover (primary: opacity ~0.88, or a fixed darker shade); nav links and ghost buttons get a faint background wash (`rgba(0,0,0,0.04)`) or an underline; no scale/shrink on press. Cards do not have a hover state — they are not generically clickable.
- **Borders vs. shadows:** a card uses a 1px border **or** a soft shadow (`0 1px 4px rgba(0,0,0,.06)`) — never both. Popovers/dropdowns use a slightly heavier shadow (`0 4px 12px rgba(0,0,0,.1)`).
- **Corner radii:** small and consistent — 4px for cards/buttons/inputs, full pill (999px/20px) for chips and badges. Nothing large or "friendly-rounded". **Sci-fi bevel update (by request):** Button and Card now use a clipped angular corner (small diagonal cut, red accent on Card) echoing the Dialog's beveled shape, in place of a rounded corner on that one edge — a deliberate divergence from the flat-corner baseline above, applied consistently across the three "panel" primitives (Dialog, Card, Button).
- **Transparency & blur:** reserved for modal/dialog scrims (`rgba(26,26,30,.55–.78)` + `backdrop-filter: blur(2–3px)`) and the Cantina surface panel — never for everyday surfaces.
- **Imagery:** illustrated/hand-drawn WebP artwork (buildings, advisor portraits, NPC characters, starfields, cantina scenes) — no photography, no pixel art. Warm-neutral, painterly, 2× HiDPI-delivered. See `docs/adr/0001-graphics-asset-format.md` in the source repo for the full spec.
- **Layout:** fixed navbar + resource bar + optional subnav stack at the top of every in-run screen; content area uses generous section spacing (32px between sections). Lobby is a centered 56rem column. Colony view is a two-column grid (canvas + fixed 320px sidebar) that stacks on mobile.
- **Responsive breakpoints (fixed set, do not add more):** mobile ≤599px, tablet ≤767px, desktop ≥768px, wide ≥900px. Navigation collapses to icon-only at <1100px and a hamburger flyout at <600px.

## Iconography

- **Bootstrap Icons** (`bootstrap-icons@1.11.3`, CDN) is the system's only icon set — loaded via `<link>` in every layout, used as `<i class="bi bi-hexagon">` inline glyphs throughout the navbar, entity chips, and status indicators. No custom icon font, no SVG icon sprite.
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
- The "End Sol" trigger is a normal `Button` (no dedicated component) with a `Dialog` confirm step for unspent AP — kept consistent with the rest of the button system rather than a bespoke primitive.
- Advisors, Techtree, Hangar, and Cantina screens were read for tone/asset reference but not built out as full UI-kit screens (time-boxed to Lobby + Colony); their controls are still covered by the shared `components/`.
- Color discrepancy noted above (`#8c2030` vs. legacy `#c0392b`) — flagging in case the team wants one canonical value confirmed.

**Ask:** tell me which of the above to prioritize next — finishing the other four in-run screens (Advisors/Techtree/Hangar/Cantina), building out the real hex-grid + Sol-Report as interactive components, or confirming the canonical accent red — and I'll iterate.
