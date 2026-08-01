/**
 * Linear or segmented progress track — Sol/run progress, or per-AP-step construction/repair investment.
 */
export interface ProgressBarProps {
  value: number;
  max: number;
  /** Segmented (discrete AP-step) rendering vs. a continuous fill. */
  segmented?: boolean;
  segments?: number;
  color?: string;
}
