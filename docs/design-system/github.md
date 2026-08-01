repo: nouron/nouron
branch: master
path: (whole repo)

## Last sync
date: 2026-07-31T18:21:35Z

### Updated in this project
- Read `docs/design-guide.md` (binding May-2026 UI spec) and derived the full token set + 12-component inventory from it.
- Copied illustration samples (buildings, advisors, characters, cantina/starfield backgrounds, favicon) into `assets/`.
- Built the Colony UI kit (Lobby + in-run Colony screen) from `resources/views/lobby/index.blade.php`, `resources/views/layouts/colony.blade.php`, and `public/css/{colony,resources,lobby}.css`.

## Screen map
| Design system screen | Repo source |
|---|---|
| `ui_kits/colony/LobbyScreen.jsx` | `resources/views/lobby/index.blade.php`, `public/css/lobby.css` |
| `ui_kits/colony/ColonyScreen.jsx` | `resources/views/layouts/colony.blade.php`, `public/css/colony.css`, `public/css/resources.css` |
| `components/navigation/Navbar.jsx` | `resources/views/layouts/colony.blade.php` (`<header class="colony-header">`) |
| `components/data/ResourceChip.jsx`, `ResourceBar.jsx`, `APChip.jsx` | `resources/views/resources/resourcebar.blade.php`, `public/css/resources.css` |
| `components/data/EntityChip.jsx` | `resources/views/components/entity-chip.blade.php` |
| Colors/type/spacing tokens | `docs/design-guide.md` |
