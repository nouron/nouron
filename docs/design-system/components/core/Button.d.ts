/**
 * Primary interactive control — brand-red primary, outline secondary, or textual ghost.
 */
export interface ButtonProps {
  /** Visual treatment. */
  variant?: "primary" | "secondary" | "ghost";
  disabled?: boolean;
  /** When set, renders a fixed AP-cost chip on the right edge (see design-guide §5.5). */
  apCost?: number | null;
  apType?: "build" | "nav" | "research" | "economy" | "strategy";
  children?: React.ReactNode;
  onClick?: () => void;
}
