**Superseded layout (2026-11):** the live game navigates through a fixed tile bar at the bottom (`BottomNav.svelte`); the top bar holds only the wordmark, trust, AP ring, Sol counter with End-Sol button, and the user menu.

The original in-run navigation — always white, never dark (design-guide §5.1: legacy dark navbar is being phased out). Logo is Libre Baskerville text only, no icon mark.

```jsx
<Navbar
  items={[{key:"colony",label:"Kolonie",icon:"bi-hexagon"},{key:"hangar",label:"Hangar",icon:"bi-rocket",locked:true}]}
  active="colony"
  rightSlot={<Button variant="primary" onClick={next}>End Sol</Button>}
/>
```

Locked items render dimmed (45% opacity) and are non-interactive. Requires Bootstrap Icons loaded (`bootstrap-icons` CDN CSS) for the `icon` glyphs.
