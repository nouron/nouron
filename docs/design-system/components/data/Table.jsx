import React from "react";
export function Table({columns,rows}) {
  return (
    <table style={{width:"100%",borderCollapse:"collapse",fontSize:"0.9rem",fontFamily:"var(--font-body)"}}>
      <thead>
        <tr>
          {columns.map((c) => (
            <th key={c.key} style={{padding:"0.55rem 0.75rem",textAlign:c.numeric?"right":"left",verticalAlign:"middle",fontWeight:600,fontSize:"0.8rem",textTransform:"uppercase",letterSpacing:"0.04em",color:"var(--color-text-secondary)",borderBottom:"2px solid var(--color-border)"}}>{c.label}</th>
          ))}
        </tr>
      </thead>
      <tbody>
        {rows.map((row,i) => (
          <tr key={i} style={{borderBottom: i===rows.length-1?"none":"1px solid var(--color-border)"}}>
            {columns.map((c) => (
              <td key={c.key} style={{padding:"0.55rem 0.75rem",textAlign:c.numeric?"right":"left",verticalAlign:"middle",fontVariantNumeric:c.numeric?"tabular-nums":undefined,color:"var(--color-text-primary)"}}>{row[c.key]}</td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  );
}
