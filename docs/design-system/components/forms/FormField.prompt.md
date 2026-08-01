Label/control/hint-or-error stack — the wrapper pattern seen around every input in `.hangar-form` and the auth forms (label above, field below, inline error under).

```jsx
<FormField label="Kolonie-Name" htmlFor="name">
  <Input id="name" value={name} onChange={e => setName(e.target.value)} />
</FormField>
```

**Intentional addition:** not a family named in the source, but the label+control+error stack it consistently used inline — factored out to avoid repeating it in every screen.
