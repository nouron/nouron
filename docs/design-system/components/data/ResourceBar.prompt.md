**Superseded layout (2026-11-14):** in the live game Sol, trust and AP sit in the header (AP as a segmented ring) and the materials sit in a collapsible rail on the left edge that never shifts page content — see "Interaction patterns" in the readme. This React component keeps the old horizontal row and is a reference for chip styling only.

Not shown on the Lobby or on screens without an active run.

```jsx
<ResourceBar sol={42} credits={3000} supply={12} trust={68} resources={[{abbr:"Rg",amount:200},{abbr:"Co",amount:0}]} />
```

Rail order is fixed: Credits/Supply → tradable resources. Secondary resources hide entirely at zero.
