/**
 * Bounded container for one clearly-scoped entity (a run, an advisor, a building) — never a general layout box.
 */
export interface CardProps {
  title?: string;
  /** Status badge/pill rendered top-right of the header. */
  badge?: React.ReactNode;
  footer?: React.ReactNode;
  children?: React.ReactNode;
}
