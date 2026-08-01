import React from "react";
const ICONS = {building:"bi-hexagon",knowledge:"bi-book",resource:"bi-layers",ship:"bi-rocket-takeoff",advisor:"bi-person-badge",research:"bi-diagram-3"};
export function EntityChip({type="resource",label,level,description}) {
  const [open,setOpen] = React.useState(false);
  return (
    <span
      onMouseEnter={()=>setOpen(true)} onMouseLeave={()=>setOpen(false)} onClick={()=>setOpen(o=>!o)}
      style={{position:"relative",display:"inline-flex",alignItems:"center",gap:"0.3rem",padding:"2px 8px",borderRadius:"10px",background:"var(--color-surface)",border:"1px solid var(--color-border)",fontSize:"0.8rem",cursor:"pointer",fontFamily:"var(--font-body)"}}
    >
      <i className={"bi "+(ICONS[type]||"bi-circle")} aria-hidden="true" />{label}
      {open && (description || level != null) && (
        <span style={{position:"absolute",top:"calc(100% + 6px)",left:"50%",transform:"translateX(-50%)",background:"#fff",border:"1px solid var(--color-border)",borderRadius:"6px",boxShadow:"var(--shadow-dropdown)",padding:"0.5rem 0.75rem",minWidth:"200px",zIndex:300,fontSize:"0.78rem",color:"var(--color-text-secondary)",whiteSpace:"normal"}}>
          {level != null && <div style={{color:"var(--color-text-primary)",marginBottom:"0.2rem"}}>Level {level}</div>}
          {description}
        </span>
      )}
    </span>
  );
}
