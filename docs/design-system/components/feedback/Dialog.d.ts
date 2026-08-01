import { ReactNode } from "react";
export interface DialogProps {
  open: boolean;
  onClose?: () => void;
  title?: string;
  children?: ReactNode;
  footer?: ReactNode;
  /** CSS max-width of the panel, e.g. "500px". Default "500px". */
  width?: string;
}
export function Dialog(props: DialogProps): JSX.Element | null;
