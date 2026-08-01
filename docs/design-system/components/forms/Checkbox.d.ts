export interface CheckboxProps {
  checked?: boolean;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  label?: string;
  disabled?: boolean;
  id?: string;
  name?: string;
}
export function Checkbox(props: CheckboxProps): JSX.Element;
