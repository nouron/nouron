Modal confirm/info dialog for destructive or blocking actions (Sol-advance confirm, hire/fire advisor, Nexus request) — use when a native `<dialog>` would normally appear in-game.

```jsx
<Dialog open={open} onClose={() => setOpen(false)} title="Sol beenden"
  footer={<><Button variant="secondary" onClick={() => setOpen(false)}>Weiterspielen</Button><Button variant="primary" onClick={confirm}>Sol beenden</Button></>}>
  <p>Ungenutzte AP verfallen am Sol-Ende.</p>
</Dialog>
```

Notable: beveled top-right corner (`clip-path`) + 3px red accent stripe on the left edge is the signature shape — reused from `dialogs.css`'s `.sol-modal`. Backdrop is `rgba(14,14,26,.65)` + 3px blur. Omit `title` to skip the header; omit `footer` to skip the action row. Escape key and backdrop click both close.
