import React from "react";
const NOTCH = 12, BW = 2;
const bevel = (n) => `polygon(0 0, calc(100% - ${n}px) 0, 100% ${n}px, 100% 100%, ${n}px 100%, 0 calc(100% - ${n}px))`;
const BASE = {fontFamily:"var(--font-body)",fontWeight:700,fontSize:"var(--text-button-size)",letterSpacing:"0.08em",textTransform:"uppercase",clipPath:bevel(NOTCH),cursor:"pointer",padding:`${BW}px`,border:"none",transition:"background 0.15s",display:"inline-flex",position:"relative",outline:"none",boxSizing:"border-box"};
const INNER_BASE = {clipPath:bevel(NOTCH-BW),display:"flex",alignItems:"center",justifyContent:"center",gap:"0.4rem",padding:"0.6rem 1.2rem",width:"100%",transition:"background 0.15s, color 0.15s"};
const VARIANTS = {
  primary:{lineColor:"var(--color-accent)",fg:"var(--color-accent)"},
  secondary:{lineColor:"var(--color-text-primary)",fg:"var(--color-text-primary)"},
  ghost:{lineColor:"var(--color-border-strong)",fg:"var(--color-text-secondary)"},
};
export function Button({variant="primary",disabled=false,apCost=null,apType="build",children,onClick,style}) {
  const [hover,setHover] = React.useState(false);
  const v = VARIANTS[variant] || VARIANTS.primary;
  let fill = "#fff", color = v.fg;
  if (hover && !disabled) {
    if (variant==="primary") { fill = "var(--color-accent)"; color = "#fff"; }
    if (variant==="secondary") { fill = "var(--color-text-primary)"; color = "#fff"; }
    if (variant==="ghost") { fill = "var(--color-surface)"; }
  }
  return (
    <button
      type="button"
      disabled={disabled}
      onClick={onClick}
      onMouseEnter={()=>setHover(true)}
      onMouseLeave={()=>setHover(false)}
      style={{...BASE,background:v.lineColor,opacity:disabled?0.45:1,pointerEvents:disabled?"none":"auto",width:apCost?"100%":undefined,...style}}
    >
      <span style={{...INNER_BASE,background:fill,color,justifyContent:apCost?"space-between":"center"}}>
        <span>{children}</span>
        {apCost != null && <APBadge amount={apCost} type={apType} />}
      </span>
    </button>
  );
}
function APBadge({amount,type}) {
  const colors = {build:["var(--ap-build-bg)","var(--ap-build-fg)"],nav:["var(--ap-nav-bg)","var(--ap-nav-fg)"],research:["var(--ap-research-bg)","var(--ap-research-fg)"],economy:["var(--ap-economy-bg)","var(--ap-economy-fg)"],strategy:["var(--ap-strategy-bg)","var(--ap-strategy-fg)"]};
  const [bg,fg] = colors[type] || colors.build;
  return <span style={{flexShrink:0,fontSize:"0.72rem",fontWeight:600,padding:"0.2rem 0.55rem",borderRadius:"var(--radius-round)",background:bg,color:fg,whiteSpace:"nowrap"}}>{amount} AP</span>;
}
