import React from "react";
const AP_COLORS = {
  nav:["var(--ap-nav-bg)","var(--ap-nav-fg)"],
  build:["var(--ap-build-bg)","var(--ap-build-fg)"],
  research:["var(--ap-research-bg)","var(--ap-research-fg)"],
  economy:["var(--ap-economy-bg)","var(--ap-economy-fg)"],
  strategy:["var(--ap-strategy-bg)","var(--ap-strategy-fg)"],
  neutral:["var(--ap-neutral-bg)","var(--ap-neutral-fg)"],
};
export function APChip({type="neutral",children}) {
  const [bg,fg] = AP_COLORS[type] || AP_COLORS.neutral;
  return <span style={{fontSize:"0.72rem",fontWeight:600,padding:"0.2rem 0.55rem",borderRadius:"var(--radius-round)",whiteSpace:"nowrap",background:bg,color:fg,fontFamily:"var(--font-body)"}}>{children}</span>;
}
