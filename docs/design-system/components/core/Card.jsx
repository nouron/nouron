import React from "react";
export function Card({title,badge,footer,children,style}) {
  const NOTCH = 16, BW = 2;
  const bevel = (n) => `polygon(0 0, calc(100% - ${n}px) 0, 100% ${n}px, 100% 100%, 0 100%)`;
  return (
    <div style={{position:"relative",display:"flex",clipPath:bevel(NOTCH),background:"var(--color-accent)",padding:`${BW}px`,boxSizing:"border-box",...style}}>
      <article style={{clipPath:bevel(NOTCH-BW),background:"var(--color-bg)",padding:"var(--card-padding)",fontFamily:"var(--font-body)",flex:1,minWidth:0}}>
      {(title || badge) && (
        <header style={{display:"flex",alignItems:"baseline",justifyContent:"space-between",gap:"0.5rem",marginBottom:"0.5rem"}}>
          {title && <h3 style={{margin:0,fontSize:"var(--text-h3-size)",fontWeight:"var(--text-h3-weight)",color:"var(--color-text-primary)"}}>{title}</h3>}
          {badge}
        </header>
      )}
      <div>{children}</div>
      {footer && <footer style={{marginTop:"1rem",display:"flex",gap:"0.5rem",flexWrap:"wrap",alignItems:"center"}}>{footer}</footer>}
      </article>
    </div>
  );
}
