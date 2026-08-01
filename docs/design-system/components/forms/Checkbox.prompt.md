Standard square checkbox with inline label (login "remember me", Sol-confirm "skip next time").

```jsx
<Checkbox checked={remember} onChange={e => setRemember(e.target.checked)} label="Angemeldet bleiben" />
```

Uses native `accent-color: var(--color-accent)` rather than a hand-built control — matches the source's plain `<input type="checkbox">` usage. For the pill/toggle variant seen in Settings and Hangar dialogs, use `Switch` instead.
