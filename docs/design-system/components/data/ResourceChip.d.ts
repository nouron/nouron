/**
 * Colored outline pill for one resource/currency amount — the game's most-repeated atom (res-chip).
 */
export interface ResourceChipProps {
  /** Fixed abbreviation — never translated (Cr, Rg, Co, Or, Sup, Tr, Sol, NX). */
  abbr: "Cr" | "Sup" | "Rg" | "Co" | "Or" | "Tr" | "Sol" | "NX";
  amount: number | string;
  tone?: "default" | "warning" | "danger";
  /** Zero-amount state — dashed border, 45% opacity. */
  empty?: boolean;
}
