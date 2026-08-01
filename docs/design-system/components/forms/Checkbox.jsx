import React from "react";
export function Checkbox({checked,onChange,label,disabled=false,id,name}) {
  return (
    <label htmlFor={id} style={{display:"inline-flex",alignItems:"center",gap:"0.55rem",fontFamily:"var(--font-body)",fontSize:"0.85rem",color:"var(--color-text-primary)",cursor:disabled?"not-allowed":"pointer",opacity:disabled?0.45:1,userSelect:"none"}}>
      <input type="checkbox" id={id} name={name} checked={checked} onChange={onChange} disabled={disabled}
        style={{width:"16px",height:"16px",margin:0,accentColor:"var(--color-accent)",cursor:disabled?"not-allowed":"pointer",flexShrink:0}} />
      {label}
    </label>
  );
}
