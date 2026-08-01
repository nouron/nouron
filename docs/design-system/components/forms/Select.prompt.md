Native `<select>` restyled to match `Input` (used for mission-target and pending-ship pickers in Hangar).

```jsx
<Select value={target} onChange={e => setTarget(e.target.value)} options={[{value:"a",label:"Sektor A"},{value:"b",label:"Sektor B"}]} />
```

Chevron uses Bootstrap Icons (`bi-chevron-down`), the system's icon set — requires the CDN stylesheet loaded alongside.
