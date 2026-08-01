/**
 * The horizontal resource strip below the navbar — Sol counter, then primary economy stats, then tradable resources.
 */
export interface ResourceBarProps {
  sol?: number;
  credits?: number;
  supply?: number;
  trust?: number;
  /** Secondary tradable resources — shown only when amount > 0. */
  resources?: { abbr: "Rg" | "Co" | "Or" | "NX"; amount: number | string; tone?: "default" | "warning" | "danger" }[];
}
