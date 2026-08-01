import React from "react";
export function StatusBadge({tone="neutral",children}) {
  const map = {
    success:{background:"var(--color-success)",color:"#fff"},
    danger:{background:"var(--color-danger)",color:"#fff"},
    warning:{background:"var(--color-warning-bg)",color:"var(--color-warning-fg)",border:"1px solid var(--color-warning)"},
    neutral:{background:"var(--color-surface)",color:"var(--color-text-secondary)",border:"1px solid var(--color-border)"},
  };
  return (
    <span style={{display:"inline-block",padding:"0.15em 0.6em",borderRadius:"0.3em",fontSize:"0.78rem",fontWeight:600,textTransform:"uppercase",letterSpacing:"0.03em",fontFamily:"var(--font-body)",whiteSpace:"nowrap",...map[tone]}}>{children}</span>
  );
}
