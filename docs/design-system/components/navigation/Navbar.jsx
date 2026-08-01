import React from "react";
export function Navbar({items=[],active,rightSlot,onSelect}) {
  return (
    <nav style={{display:"flex",alignItems:"center",justifyContent:"space-between",height:"60px",padding:"0 1rem",background:"var(--color-navbar-bg)",borderBottom:"1px solid var(--color-navbar-border)",fontFamily:"var(--font-body)"}}>
      <a href="#" style={{fontFamily:"var(--font-display)",fontWeight:400,letterSpacing:"0.45em",textTransform:"uppercase",color:"var(--color-text-primary)",fontSize:"0.9rem",textDecoration:"none"}}>Nouron</a>
      <ul style={{display:"flex",flex:1,justifyContent:"center",gap:"1rem",listStyle:"none",margin:0,padding:0}}>
        {items.map((it) => (
          <li key={it.key}>
            <a
              href="#"
              onClick={(e)=>{e.preventDefault(); !it.locked && onSelect && onSelect(it.key);}}
              style={{
                display:"inline-flex",alignItems:"center",gap:"0.35rem",fontSize:"0.85rem",padding:"10px",textDecoration:"none",whiteSpace:"nowrap",
                color: it.locked ? "var(--color-text-secondary)" : (active===it.key ? "var(--color-accent)" : "#4a4a58"),
                fontWeight: active===it.key ? 600 : 400,
                opacity: it.locked ? 0.45 : 1,
                background: active===it.key ? "var(--color-accent-tint)" : "transparent",
                borderRadius:"6px",
                cursor: it.locked ? "default" : "pointer",
              }}
            >
              {it.icon && <i className={"bi "+it.icon} aria-hidden="true" />}{it.label}
            </a>
          </li>
        ))}
      </ul>
      <div style={{display:"flex",alignItems:"center",gap:"0.75rem"}}>{rightSlot}</div>
    </nav>
  );
}
