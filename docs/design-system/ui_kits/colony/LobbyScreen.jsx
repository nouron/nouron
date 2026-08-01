const { Card, Button, StatusBadge, Table } = window.NouronDesignSystem_019dc5;

const ACTIVE = { name: "Springfield", sol: 42, limit: 100 };

function LobbyScreen({ onEnterColony }) {
  const HIGHSCORE = [
    { m: "Run #12", s: <StatusBadge tone="success">Completed</StatusBadge>, sol: "100/100", sc: "48,200" },
    { m: "Run #11", s: <StatusBadge tone="danger">Failed</StatusBadge>, sol: "37/100", sc: "12,050" },
    { m: "Run #10", s: <StatusBadge tone="success">Completed</StatusBadge>, sol: "100/100", sc: "44,900" },
  ];
  return (
    <div style={{maxWidth:"56rem",margin:"0 auto",padding:"2rem 1.5rem 3rem",fontFamily:"var(--font-body)",color:"var(--color-text-primary)"}}>
      <div style={{marginBottom:"1.75rem"}}>
        <h1 style={{fontFamily:"var(--font-display)",fontWeight:400,textTransform:"uppercase",letterSpacing:"0.45em",fontSize:"2rem",margin:"0 0 0.25rem"}}>Missions</h1>
        <p style={{color:"var(--color-text-secondary)",margin:0}}>Every colony is a fresh attempt against decay, scarcity, and the silence of Zone Ypsilon-7.</p>
      </div>

      <h2 style={{fontSize:"1.1rem",fontWeight:600,margin:"1.75rem 0 0.25rem",paddingBottom:"0.35rem",borderBottom:"1px solid var(--color-border)"}}>Active Runs</h2>
      <div style={{display:"grid",gridTemplateColumns:"repeat(auto-fill,minmax(18rem,1fr))",gap:"1rem",marginTop:"0.75rem"}}>
        <Card title={ACTIVE.name} footer={<><Button variant="primary" onClick={onEnterColony}>Continue</Button><Button variant="secondary">Abandon</Button></>}>
          <p style={{fontSize:"0.85rem",color:"var(--color-text-secondary)",margin:"0 0 0.5rem"}}>Sol {ACTIVE.sol} / {ACTIVE.limit}</p>
          <div style={{position:"relative",height:"0.5rem",background:"#eee",borderRadius:"4px",overflow:"hidden"}}>
            <div style={{height:"100%",width:(ACTIVE.sol/ACTIVE.limit*100)+"%",background:"var(--color-accent)",borderRadius:"4px"}} />
          </div>
        </Card>
      </div>

      <h2 style={{fontSize:"1.1rem",fontWeight:600,margin:"1.75rem 0 0.25rem",paddingBottom:"0.35rem",borderBottom:"1px solid var(--color-border)"}}>Highscore</h2>
      <div style={{marginTop:"0.75rem"}}>
        <Table columns={[{key:"m",label:"Mission"},{key:"s",label:"Status"},{key:"sol",label:"Sol"},{key:"sc",label:"Score",numeric:true}]} rows={HIGHSCORE} />
      </div>
    </div>
  );
}
