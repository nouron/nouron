import React from "react";
export function Switch({checked,onChange,label,disabled=false,id,name}) {
  return (
    <label htmlFor={id} style={{display:"inline-flex",alignItems:"center",gap:"0.55rem",fontFamily:"var(--font-body)",fontSize:"0.85rem",color:"var(--color-text-primary)",cursor:disabled?"not-allowed":"pointer",opacity:disabled?0.45:1,userSelect:"none"}}>
      <span style={{position:"relative",width:"34px",height:"20px",flexShrink:0,borderRadius:"var(--radius-round)",background:checked?"var(--color-accent)":"var(--color-border-strong)",transition:"background 0.15s"}}>
        <input type="checkbox" role="switch" id={id} name={name} checked={checked} onChange={onChange} disabled={disabled}
          style={{position:"absolute",inset:0,opacity:0,margin:0,cursor:disabled?"not-allowed":"pointer"}} />
        <span style={{position:"absolute",top:"2px",left:checked?"16px":"2px",width:"16px",height:"16px",borderRadius:"50%",background:"#fff",boxShadow:"0 1px 2px rgba(0,0,0,0.25)",transition:"left 0.15s"}} />
      </span>
      {label}
    </label>
  );
}
