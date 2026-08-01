Single-line text/number/email/password field, styled after the login/register and Hangar dialog inputs.

```jsx
<Input type="text" value={name} onChange={e => setName(e.target.value)} placeholder="Kolonie-Name" />
```

`#fafafa` fill, `1px solid #d0d0dc` border, `4px` radius, accent-red focus ring (`box-shadow` + border color) instead of the browser default blue outline. Pair with `FormField` for label/error.
