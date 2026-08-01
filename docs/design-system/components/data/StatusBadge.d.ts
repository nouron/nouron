/**
 * Uppercase status pill (run outcome, warning) — semantic colors are separate from the brand accent.
 */
export interface StatusBadgeProps {
  tone?: "success" | "danger" | "warning" | "neutral";
  children?: React.ReactNode;
}
