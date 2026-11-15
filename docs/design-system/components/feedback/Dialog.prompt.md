Modal confirm/info dialog for actions that lose something or block the game (Sol-advance confirm with unspent AP, fire advisor, tear down a building, Nexus request). Do not use it to confirm an action whose cost and effect the card already shows — hiring an advisor acts on click since 2026-11-14 (see "Interaction patterns" in the readme). Otherwise — use when a native `<dialog>` would normally appear in-game.

```jsx
<Dialog open={open} onClose={() => setOpen(false)} title="Sol beenden"
  footer={<><Button variant="secondary" onClick={() => setOpen(false)}>Weiterspielen</Button><Button variant="primary" onClick={confirm}>Sol beenden</Button></>}>
  <p>Ungenutzte AP verfallen am Sol-Ende.</p>
</Dialog>
```

Notable: beveled top-right corner (`clip-path`) + 3px red accent stripe on the left edge is the signature shape — reused from `dialogs.css`'s `.sol-modal`. Backdrop is `rgba(14,14,26,.65)` + 3px blur. Omit `title` to skip the header; omit `footer` to skip the action row. Escape key and backdrop click both close.
