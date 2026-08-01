export interface RangeSliderProps {
  value?: number;
  min?: number;
  max?: number;
  step?: number;
  onChange?: (e: React.ChangeEvent<HTMLInputElement>) => void;
  disabled?: boolean;
  id?: string;
  name?: string;
}
export function RangeSlider(props: RangeSliderProps): JSX.Element;
