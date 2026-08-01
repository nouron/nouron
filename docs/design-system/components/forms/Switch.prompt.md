Pill toggle switch (`role="switch"`) for on/off settings — Nexus-credit toggle in the Hangar request dialog, onboarding-hints in Settings.

```jsx
<Switch checked={useNexusCredit} onChange={e => setUseNexusCredit(e.target.checked)} label="Nexus-Kredit verwenden" />
```

Accent-red track when on (brand accent, not the source's Nexus-specific blue — kept consistent with the rest of the system's single accent color).
