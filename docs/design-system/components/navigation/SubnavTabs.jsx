import React from "react";
export function SubnavTabs({tabs,active,onSelect}) {
  return (
    <div style={{display:"flex",gap:"1.5rem",borderBottom:"1px solid var(--color-border)",background:"var(--color-bg)",padding:"0 1rem",fontFamily:"var(--font-body)"}}>
      {tabs.map((t) => (
        <a
          key={t.key}
          href="#"
          onClick={(e)=>{e.preventDefault(); onSelect && onSelect(t.key);}}
          style={{
            padding:"0.6rem 0",fontSize:"0.875rem",textDecoration:"none",
            color: active===t.key ? "var(--color-accent)" : "var(--color-text-secondary)",
            borderBottom: active===t.key ? "2px solid var(--color-accent)" : "2px solid transparent",
          }}
        >{t.label}</a>
      ))}
    </div>
  );
}
