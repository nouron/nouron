/**
 * Inline glossary pill linking to a building/knowledge/resource/ship/advisor/research — hover or tap for a tooltip.
 */
export interface EntityChipProps {
  type?: "building" | "knowledge" | "resource" | "ship" | "advisor" | "research";
  label: string;
  level?: number;
  description?: string;
}
