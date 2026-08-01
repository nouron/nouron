Standard data table, e.g. the Lobby highscore board.

```jsx
<Table
  columns={[{key:"mission",label:"Mission"},{key:"score",label:"Score",numeric:true}]}
  rows={[{mission:"Run #12", score:"48,200"}]}
/>
```

Row hover isn't built in here (kept purely presentational) — add background-on-hover per row in the consuming screen if needed.
