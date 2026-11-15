Sits at the top of the page content (the resource bar is a left rail since 2026-11) for screens with internal sections. No dark background, ever — text links only, accent underline marks the active tab.

```jsx
<SubnavTabs tabs={[{key:"overview",label:"Overview"},{key:"log",label:"Log"}]} active="overview" onSelect={setTab} />
```
