import { cn } from '@/utils'
import { RadioGroup as RadioGroupPrimitive } from '@base-ui/react/radio-group'

export interface RadioGroupProps extends RadioGroupPrimitive.Props {}

const RadioGroup = ({ className, ...props }: RadioGroupProps) => (
    <RadioGroupPrimitive
        data-slot='radio-group'
        className={cn('grid gap-2', className)}
        {...props}
    />
)

export default RadioGroup
