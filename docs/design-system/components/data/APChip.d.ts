/**
 * Small pill showing a remaining/spent Action Point pool by type, color-coded per AP category.
 */
export interface APChipProps {
  type?: "nav" | "build" | "research" | "economy" | "strategy" | "neutral";
  children?: React.ReactNode;
}
