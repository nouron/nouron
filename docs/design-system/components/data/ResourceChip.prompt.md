The resource pill — an "existing, well-functioning pattern kept unchanged" per the design guide. Each resource has a fixed abbreviation and its own accent color; never spelled out, never translated.

```jsx
<ResourceChip abbr="Cr" amount={3000} />
<ResourceChip abbr="Rg" amount={0} empty />
<ResourceChip abbr="NX" amount="8,400 / 12,000" tone="warning" />
```

Sol chip is borderless/transparent — reserved for the absolute day counter. Compose several inside `ResourceBar`, separated by a vertical `res-divider` rule.
