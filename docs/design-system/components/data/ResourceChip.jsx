import React from "react";
const RES_COLORS = {
  Cr:["var(--res-credits-bg)","var(--res-credits)"],
  Sup:["var(--res-supply-bg)","var(--res-supply)"],
  Rg:["var(--res-regolith-bg)","var(--res-regolith)"],
  Co:["var(--res-compounds-bg)","var(--res-compounds)"],
  Or:["var(--res-organics-bg)","var(--res-organics)"],
  Tr:["var(--res-trust-bg)","var(--res-trust)"],
  Sol:["var(--res-sol-bg)","var(--res-sol)"],
  NX:["var(--res-nexus-debt-bg)","var(--res-nexus-debt)"],
};
export function ResourceChip({abbr,amount,tone,empty=false}) {
  const [bg,border] = RES_COLORS[abbr] || ["#fff","rgba(0,0,0,0.12)"];
  const isSol = abbr === "Sol";
  const toneStyle = tone === "warning" ? {background:"var(--color-warning-bg)",borderColor:"var(--color-warning)",color:"var(--color-warning-fg)"}
    : tone === "danger" ? {background:"var(--color-danger-bg)",borderColor:"var(--color-accent)",color:"var(--color-accent)"}
    : isSol ? {background:"transparent",borderColor:"transparent"}
    : {background:bg,borderColor:border};
  return (
    <span style={{display:"inline-flex",alignItems:"center",gap:"4px",padding:"3px 10px 3px 8px",borderRadius:"var(--radius-round)",fontSize:"0.7rem",fontWeight:700,border:"1px solid",color:"#333",whiteSpace:"nowrap",opacity:empty?0.45:1,borderStyle:empty?"dashed":"solid",...toneStyle}}>
      <span style={{fontSize:"0.7rem",fontWeight:700,opacity:0.65,textTransform:"uppercase",letterSpacing:"0.03em"}}>{abbr}</span>
      <span style={{fontVariantNumeric:"tabular-nums"}}>{amount}</span>
    </span>
  );
}
