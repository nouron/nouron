const { Navbar, ResourceBar, SubnavTabs, Card, Button, Dialog, EntityChip, ProgressBar } = window.NouronDesignSystem_019dc5;

const NAV_ITEMS = [
  { key: "colony", label: "Kolonie", icon: "bi-hexagon" },
  { key: "command_center", label: "Command Center", icon: "bi-diagram-2" },
  { key: "advisors", label: "Berater", icon: "bi-people" },
  { key: "techtree", label: "Techtree", icon: "bi-diagram-3" },
  { key: "cantina", label: "Cantina", icon: "bi-cup-hot" },
  { key: "hangar", label: "Hangar", icon: "bi-rocket", locked: true },
  { key: "log", label: "Protokoll", icon: "bi-journal-text" },
];

const TILES = [
  { id: "cc", x: 260, y: 120, fill: "#7ec87e", stroke: "#2e7d32", label: "Command Center", type: "Command Center", level: 2 },
  { id: "harv", x: 340, y: 165, fill: "#7fb5dc", stroke: "#5090c0", label: "Harvester", type: "Regolith deposit", level: 1 },
  { id: "hab", x: 180, y: 165, fill: "#c8cdd6", stroke: "#a0a8b4", label: "Residential Habitat", type: "Buildable", level: 1 },
  { id: "haz", x: 260, y: 210, fill: "#e8b87a", stroke: "#c08040", label: "Hazard zone", type: "Hazard", level: null },
  { id: "fog", x: 420, y: 120, fill: "#9aa4b8", stroke: "#6f7a90", label: "Unexplored", type: "Explore target", level: null },
];

function Hex({ x, y, fill, stroke, selected, onClick }) {
  const r = 34;
  const pts = Array.from({length:6}).map((_,i)=>{const a=Math.PI/180*(60*i-30);return `${x+r*Math.cos(a)},${y+r*Math.sin(a)}`;}).join(" ");
  return <polygon points={pts} fill={fill} stroke={selected?"var(--color-accent)":stroke} strokeWidth={selected?3:1.5} onClick={onClick} style={{cursor:"pointer"}} />;
}

function ColonyScreen({ onExit }) {
  const [active, setActive] = React.useState("colony");
  const [tab, setTab] = React.useState("overview");
  const [selected, setSelected] = React.useState(TILES[0]);
  const [confirmEnd, setConfirmEnd] = React.useState(false);
  const pendingAp = 2;

  return (
    <div style={{fontFamily:"var(--font-body)",color:"var(--color-text-primary)",background:"#fff"}}>
      <Navbar items={NAV_ITEMS} active={active} onSelect={(k)=>{ if(k==="colony") setActive(k); else setActive(k); }} rightSlot={<><Button variant="ghost" onClick={onExit}>Exit</Button><Button variant="primary" onClick={()=>pendingAp>0?setConfirmEnd(true):null}>End Sol</Button></>} />
      <ResourceBar sol={42} credits={3120} supply={18} trust={64} resources={[{abbr:"Rg",amount:210},{abbr:"Co",amount:40},{abbr:"Or",amount:12}]} />
      <SubnavTabs tabs={[{key:"overview",label:"Overview"},{key:"legend",label:"Legend"}]} active={tab} onSelect={setTab} />

      <div style={{display:"grid",gridTemplateColumns:"1fr 320px",minHeight:"420px"}}>
        <div style={{background:"#fafafa",borderRight:"1px solid var(--color-border)",display:"flex",alignItems:"center",justifyContent:"center",padding:"1.5rem"}}>
          <svg width="480" height="300" viewBox="0 0 480 300">
            {TILES.map((t) => <Hex key={t.id} {...t} selected={selected.id===t.id} onClick={()=>setSelected(t)} />)}
          </svg>
        </div>
        <div style={{padding:"1.25rem",display:"flex",flexDirection:"column",gap:"1rem"}}>
          <div style={{paddingBottom:"0.6rem",borderBottom:"2px solid var(--color-accent)"}}>
            <h3 style={{margin:0,fontSize:"1rem",fontWeight:700,textTransform:"uppercase",letterSpacing:"0.06em"}}>{selected.label}</h3>
          </div>
          <p style={{margin:0,fontSize:"0.85rem",color:"var(--color-text-secondary)"}}>{selected.type}{selected.level!=null && ` — Level ${selected.level}`}</p>
          {selected.id==="cc" && <EntityChip type="building" label="Command Center" level={2} description="Coordinates colony administration and unlocks new build tiers." />}
          {selected.level != null && (
            <div>
              <div style={{fontSize:"0.72rem",color:"#888",marginBottom:"0.25rem",display:"flex",justifyContent:"space-between"}}><span>Condition</span><span>80%</span></div>
              <ProgressBar value={80} max={100} color="#2196f3" />
            </div>
          )}
          <div style={{display:"flex",flexDirection:"column",gap:"0.5rem",marginTop:"0.5rem"}}>
            <Button variant="primary" apCost={1} apType="build">Repair</Button>
            <Button variant="secondary" apCost={2} apType="nav">Explore adjacent</Button>
          </div>
        </div>
      </div>
      <Dialog open={confirmEnd} onClose={()=>setConfirmEnd(false)} title="End Sol?" footer={<><Button variant="ghost" onClick={()=>setConfirmEnd(false)}>Keep playing</Button><Button variant="primary" onClick={()=>setConfirmEnd(false)}>End Sol anyway</Button></>}>
        <p style={{margin:0,fontSize:"0.9rem",color:"var(--color-text-secondary)",lineHeight:1.5}}>You still have {pendingAp} AP unspent this Sol. Unused AP expires at Sol end.</p>
      </Dialog>
    </div>
  );
}
