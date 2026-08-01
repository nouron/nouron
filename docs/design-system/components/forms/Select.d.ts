export interface SelectOption { value: string; label: string; }
export interface SelectProps {
  value?: string;
  onChange?: (e: React.ChangeEvent<HTMLSelectElement>) => void;
  options: SelectOption[];
  disabled?: boolean;
  id?: string;
  name?: string;
  style?: React.CSSProperties;
}
export function Select(props: SelectProps): JSX.Element;
