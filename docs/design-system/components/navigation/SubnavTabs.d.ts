/**
 * Module-level tab row directly below the resource bar (Colony, Advisors, Trade, Comm Log, Cantina).
 * @startingPoint section="Components" subtitle="Text-link tabs with accent underline on active" viewport="700x60"
 */
export interface SubnavTab {
  key: string;
  label: string;
}
export interface SubnavTabsProps {
  tabs: SubnavTab[];
  active: string;
  onSelect?: (key: string) => void;
}
