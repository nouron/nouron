Its own row directly under the navbar, never part of it — not shown on the Lobby or on screens without an active run.

```jsx
<ResourceBar sol={42} credits={3000} supply={12} trust={68} resources={[{abbr:"Rg",amount:200},{abbr:"Co",amount:0}]} />
```

Order is fixed: Sol chip → divider → Credits/Supply/Trust → divider → tradable resources. Secondary resources hide entirely at zero.
