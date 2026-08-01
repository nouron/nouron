# Colony UI Kit

Click-through recreation of Nouron's two core screens, built from `docs/design-guide.md` and the Blade/CSS in the source repo (`resources/views/lobby/index.blade.php`, `resources/views/layouts/colony.blade.php`, `public/css/colony.css`).

- **Lobby** — mission list, active-run progress, highscore table.
- **Colony** — navbar + resource bar + subnav + a simplified hex-grid canvas (structural SVG, not the production tile-rendering engine) with a tile detail sidebar.

Composed entirely from `components/` primitives (Navbar, ResourceBar, SubnavTabs, Card, Button, Dialog, Table, EntityChip, ProgressBar) — no re-implementation of those primitives here. "End Sol" is a plain `Button` + `Dialog` confirm, not a dedicated component.

Omitted / simplified vs. production: the full animated Sol-Report sequence, the real hex-grid rendering engine (`colony-hexgrid.js`), Advisors/Techtree/Hangar/Cantina screens (not built — only Colony + Lobby, to keep the kit focused; component coverage for those screens' controls still lives in `components/`).
