import { cn } from '@/utils'
import { Checkbox as CheckboxPrimitive } from '@base-ui/react/checkbox'
import { IconCheck } from '@tabler/icons-react'

export interface CheckboxProps extends CheckboxPrimitive.Root.Props {}

const Checkbox = ({ className, ...props }: CheckboxProps) => (
    <CheckboxPrimitive.Root
        data-slot='checkbox'
        // Base UI renders these controls as a `span`, not a native input or
        // button, so the `disabled` attribute never lands on the DOM and every
        // `disabled:` variant silently compiles to a rule that cannot match --
        // a disabled control looked exactly like a live one. Base UI signals
        // the state with `data-disabled` instead; that is what to style.
        className={cn(
            'border-input focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 aria-invalid:aria-checked:border-primary dark:bg-input/30 dark:aria-invalid:border-destructive/50 dark:aria-invalid:ring-destructive/40 data-checked:border-primary data-checked:bg-primary data-checked:text-primary-foreground dark:data-checked:bg-primary peer relative flex size-4 shrink-0 items-center justify-center rounded-[4px] border transition-colors outline-none after:absolute after:-inset-x-3 after:-inset-y-2 focus-visible:ring-3 aria-invalid:ring-3 data-disabled:cursor-not-allowed data-disabled:opacity-50',
            className
        )}
        {...props}
    >
        <CheckboxPrimitive.Indicator
            data-slot='checkbox-indicator'
            className='grid place-content-center text-current transition-none [&>svg]:size-3.5'
        >
            <IconCheck />
        </CheckboxPrimitive.Indicator>
    </CheckboxPrimitive.Root>
)

export default Checkbox
