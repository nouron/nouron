import React from "react";
export function RangeSlider({value,min=0,max=100,step=1,onChange,disabled=false,id,name}) {
  return (
    <input type="range" id={id} name={name} value={value} min={min} max={max} step={step} onChange={onChange} disabled={disabled}
      style={{width:"100%",accentColor:"var(--color-accent)",cursor:disabled?"not-allowed":"pointer",opacity:disabled?0.45:1}} />
  );
}
