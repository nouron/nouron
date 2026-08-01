Nouron's button — a primary red action, a secondary outline, or a textual ghost link, used for every actionable control in the colony UI.

```jsx
<Button variant="primary" onClick={endSol}>Build</Button>
<Button variant="secondary">Cancel</Button>
<Button variant="primary" apCost={1} apType="build">Repair</Button>
```

Variants: `primary` (solid accent, white text), `secondary` (outline, transparent fill), `ghost` (no border, underline on hover). Set `disabled` for a 45%-opacity non-interactive state. `apCost` + `apType` render the fixed-AP-cost chip mandated for any action with a known AP price (never for continuously-scaling AP spend).

**Sci-fi bevel motif (added on request):** clipped top-left/bottom-right corners (matches the Dialog's beveled shape) plus a red glow + inset highlight on primary hover — an intentional evolution beyond the documented flat-corner button, extending the Dialog's angular language system-wide.
