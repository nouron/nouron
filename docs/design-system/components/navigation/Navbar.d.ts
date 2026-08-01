/**
 * Fixed, always-light top navigation bar — Libre Baskerville wordmark, Bootstrap Icons nav items, right-slot for Sol button / user menu.
 * @startingPoint section="Components" subtitle="Wordmark, icon nav links, locked state, right-hand slot" viewport="900x70"
 */
export interface NavItem {
  key: string;
  label: string;
  /** Bootstrap Icons class, e.g. "bi-hexagon". */
  icon?: string;
  locked?: boolean;
}
export interface NavbarProps {
  items: NavItem[];
  active?: string;
  onSelect?: (key: string) => void;
  /** Sol button / user dropdown, right-aligned. */
  rightSlot?: React.ReactNode;
}
