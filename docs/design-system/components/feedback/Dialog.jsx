import React from "react";
export function Dialog({open,onClose,title,children,footer,width="500px"}) {
  React.useEffect(() => {
    if (!open) return;
    const onKey = (e) => { if (e.key === "Escape") onClose && onClose(); };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [open, onClose]);
  if (!open) return null;
  return (
    <div onClick={onClose} style={{position:"fixed",inset:0,background:"var(--color-scrim)",backdropFilter:"blur(3px)",WebkitBackdropFilter:"blur(3px)",display:"flex",alignItems:"center",justifyContent:"center",zIndex:1000,animation:"ds-scrim-in var(--duration-base) ease-out"}}>
      <div onClick={(e)=>e.stopPropagation()} style={{width:`min(90vw, ${width})`,maxWidth:`min(90vw, ${width})`,maxHeight:"85vh",overflow:"hidden",position:"relative",clipPath:"polygon(0 0, calc(100% - 20px) 0, 100% 20px, 100% 100%, 0 100%)",background:"linear-gradient(to right, var(--color-accent) 0, var(--color-accent) 3px, transparent 3px), #ffffff",filter:"drop-shadow(0 12px 36px rgba(14,14,46,0.2)) drop-shadow(0 2px 6px rgba(14,14,46,0.1))",animation:"ds-dialog-in var(--duration-base) ease-out",display:"flex",flexDirection:"column"}}>
        <div style={{position:"absolute",top:0,left:"3px",right:"20px",height:"1px",background:"rgba(0,0,0,0.06)"}} />
        {title && (
          <header style={{display:"flex",alignItems:"center",padding:"0.9rem 3rem 0.75rem 1.25rem",borderBottom:"1px solid rgba(0,0,0,0.07)",position:"relative",flexShrink:0}}>
            <h3 style={{margin:0,fontSize:"0.88rem",fontWeight:700,letterSpacing:"0.06em",textTransform:"uppercase",color:"var(--color-anthracite)",overflow:"hidden",textOverflow:"ellipsis",whiteSpace:"nowrap",flex:"1 1 0",minWidth:0}}>{title}</h3>
            <button onClick={onClose} aria-label="Close" style={{position:"absolute",top:"50%",right:"1.5rem",transform:"translateY(-50%)",background:"none",border:"none",cursor:"pointer",color:"#bbb",fontSize:"1rem",lineHeight:1,padding:"0.1rem 0.25rem",transition:"color 0.15s"}} onMouseEnter={(e)=>e.target.style.color="var(--color-anthracite)"} onMouseLeave={(e)=>e.target.style.color="#bbb"}>✕</button>
          </header>
        )}
        <div style={{padding:"0.9rem 1rem 0.9rem 1.25rem",overflowY:"auto",fontSize:"0.9rem",color:"var(--color-text-primary)",lineHeight:1.5}}>{children}</div>
        {footer && (
          <footer style={{borderTop:"1px solid rgba(0,0,0,0.07)",padding:"0.75rem 1rem 0.9rem 1.25rem",display:"flex",justifyContent:"flex-end",gap:"0.5rem",flexShrink:0}}>{footer}</footer>
        )}
      </div>
    </div>
  );
}
