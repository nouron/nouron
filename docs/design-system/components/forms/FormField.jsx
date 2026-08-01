import React from "react";
export function FormField({label,htmlFor,error,hint,children}) {
  return (
    <div style={{display:"flex",flexDirection:"column",gap:"0.3rem",fontFamily:"var(--font-body)"}}>
      {label && <label htmlFor={htmlFor} style={{fontFamily:"var(--font-body)",fontSize:"0.72rem",color:"var(--color-text-secondary)",fontWeight:600}}>{label}</label>}
      {children}
      {hint && !error && <span style={{fontFamily:"var(--font-body)",fontSize:"0.72rem",color:"var(--color-text-secondary)"}}>{hint}</span>}
      {error && <span style={{fontFamily:"var(--font-body)",fontSize:"0.75rem",color:"var(--color-accent)",minHeight:"1em"}}>{error}</span>}
    </div>
  );
}
