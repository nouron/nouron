import React from "react";
export function Select({value,onChange,options=[],disabled=false,id,name,style}) {
  const [focus,setFocus] = React.useState(false);
  return (
    <div style={{position:"relative",width:"100%"}}>
      <select value={value} onChange={onChange} disabled={disabled} id={id} name={name}
        onFocus={()=>setFocus(true)} onBlur={()=>setFocus(false)}
        style={{width:"100%",appearance:"none",WebkitAppearance:"none",fontFamily:"var(--font-body)",fontSize:"0.85rem",padding:"0.45rem 2rem 0.45rem 0.65rem",
          border:"1px solid var(--color-border-strong)",borderRadius:"var(--radius-sm)",background:"var(--color-input-bg)",color:"var(--color-text-primary)",
          boxSizing:"border-box",cursor:disabled?"not-allowed":"pointer",opacity:disabled?0.45:1,outline:"none",
          borderColor:focus?"var(--color-accent)":"var(--color-border-strong)",boxShadow:focus?"0 0 0 3px var(--color-accent-tint)":"none",...style}}>
        {options.map(o => <option key={o.value} value={o.value}>{o.label}</option>)}
      </select>
      <i className="bi bi-chevron-down" style={{position:"absolute",right:"0.65rem",top:"50%",transform:"translateY(-50%)",fontSize:"0.7rem",color:"var(--color-text-secondary)",pointerEvents:"none"}} />
    </div>
  );
}
