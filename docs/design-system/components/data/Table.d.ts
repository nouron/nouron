/**
 * Full-width data table — highscore boards, cost breakdowns, tech comparisons.
 * @startingPoint section="Components" subtitle="Bordered rows, uppercase muted header, numeric right-align" viewport="700x260"
 */
export interface TableColumn {
  key: string;
  label: string;
  /** Right-aligns and applies tabular-nums. */
  numeric?: boolean;
}
export interface TableProps {
  columns: TableColumn[];
  rows: Record<string, React.ReactNode>[];
}
