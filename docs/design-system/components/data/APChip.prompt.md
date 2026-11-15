**Note (2026-11):** AP are one shared pool per Sol; the remaining AP show as a segmented ring in the header, not as chips. The chip survives as the AP-cost chip on buttons and as a "+1 AP" gain marker; its tint only marks the kind of action.

Originally used on the canvas header/legend to show remaining AP pools by category — distinct from `Button`'s inline AP-cost chip, which shows a fixed price rather than a pool total.

```jsx
<APChip type="build">3 Con AP</APChip>
<APChip type="nav">0 Nav AP</APChip>
```

Colors: nav=blue, build=green, research=violet, economy=amber, strategy=rose, neutral=gray.
