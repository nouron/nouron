import React from "react";
import { ResourceChip } from "./ResourceChip.jsx";
export function ResourceBar({sol,credits,supply,trust,resources=[]}) {
  const divider = <span style={{display:"inline-block",width:"1px",height:"22px",background:"#ccc",borderRadius:"1px",margin:"0 2px",alignSelf:"center"}} />;
  return (
    <div style={{display:"flex",flexWrap:"wrap",gap:"0.5rem",justifyContent:"center",alignItems:"center",padding:"5px 12px",background:"var(--color-bg)",borderBottom:"1px solid var(--color-border)",boxShadow:"0 1px 3px rgba(0,0,0,0.04)",fontFamily:"var(--font-body)"}}>
      {sol != null && <ResourceChip abbr="Sol" amount={sol} />}
      {divider}
      {credits != null && <ResourceChip abbr="Cr" amount={credits} />}
      {supply != null && <ResourceChip abbr="Sup" amount={supply} />}
      {trust != null && <ResourceChip abbr="Tr" amount={trust} />}
      {resources.length > 0 && divider}
      {resources.map((r) => <ResourceChip key={r.abbr} {...r} />)}
    </div>
  );
}
