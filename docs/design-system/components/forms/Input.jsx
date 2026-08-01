import React from "react";
const FIELD_BASE = {width:"100%",fontFamily:"var(--font-body)",fontSize:"0.85rem",padding:"0.45rem 0.65rem",border:"1px solid var(--color-border-strong)",borderRadius:"var(--radius-sm)",background:"var(--color-input-bg)",color:"var(--color-text-primary)",boxSizing:"border-box",transition:"border-color 0.15s, box-shadow 0.15s",outline:"none"};
export function Input({type="text",value,onChange,placeholder,disabled=false,id,name,style}) {
  const [focus,setFocus] = React.useState(false);
  return (
    <input type={type} id={id} name={name} value={value} onChange={onChange} placeholder={placeholder} disabled={disabled}
      onFocus={()=>setFocus(true)} onBlur={()=>setFocus(false)}
      style={{...FIELD_BASE,opacity:disabled?0.45:1,cursor:disabled?"not-allowed":"text",
        borderColor:focus?"var(--color-accent)":"var(--color-border-strong)",
        boxShadow:focus?"0 0 0 3px var(--color-accent-tint)":"none",...style}} />
  );
}
