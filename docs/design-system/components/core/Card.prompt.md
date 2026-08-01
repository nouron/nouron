A bordered white panel used only to group a distinct entity — a lobby run, an advisor, a building detail. Never used as a generic layout wrapper (design-guide §5.4: "Cards nur für abgegrenzte Entitäten").

```jsx
<Card title="Springfield" badge={<StatusBadge tone="success">Completed</StatusBadge>} footer={<Button>Continue</Button>}>
  <p>Sol 42 / 100</p>
</Card>
```

Border OR shadow, never both. 4px radius, 1.5rem internal padding. Grid multiple cards with `gap: 1rem` and `grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr))`.

**Sci-fi bevel motif (added on request):** top-right corner is clipped with a small red diagonal accent, echoing the Dialog's beveled shape — a deliberate extension of the angular sci-fi language beyond the original flat-corner spec.
