export interface InputProps {
  type?: "text" | "number" | "email" | "password";
  value?: string | number;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  placeholder?: string;
  disabled?: boolean;
  id?: string;
  name?: string;
  style?: React.CSSProperties;
}
export function Input(props: InputProps): JSX.Element;
