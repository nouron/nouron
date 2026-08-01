import React from "react";
export function ProgressBar({value,max,segmented=false,segments=10,color="var(--color-accent)"}) {
  const pct = Math.max(0,Math.min(100,(value/max)*100));
  if (!segmented) {
    return (
      <div style={{position:"relative",height:"0.5rem",background:"#eee",borderRadius:"4px",overflow:"hidden"}}>
        <div style={{height:"100%",borderRadius:"4px",width:pct+"%",background:color,transition:"width 0.3s ease"}} />
      </div>
    );
  }
  const filled = Math.round((value/max)*segments);
  return (
    <div style={{display:"flex",gap:"2px",height:"0.28rem"}}>
      {Array.from({length:segments}).map((_,i) => (
        <div key={i} style={{flex:"1 1 0",minWidth:0,borderRadius:"1px",background:i<filled?color:"rgba(0,0,0,0.14)",transition:"background 0.15s ease"}} />
      ))}
    </div>
  );
}
