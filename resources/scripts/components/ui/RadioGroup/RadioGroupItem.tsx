import { cn } from '@/utils'
import { Radio as RadioPrimitive } from '@base-ui/react/radio'

export interface RadioGroupItemProps extends RadioPrimitive.Root.Props {}

/**
 * Chrome mirrors the shared Checkbox: nova's `border-input` resting state (not
 * the old `border-primary`), no shadow, a `ring-3` focus ring, invalid/disabled
 * treatment, and the same `after:` hit-target expansion so the 16px control is
 * comfortably tappable.
 *
 * ⚠️ Base UI state attributes, not Radix's: `data-checked`/`data-unchecked`, not
 * `data-state=checked`. A ported Radix selector compiles fine and silently never
 * matches.
 */
const RadioGroupItem = ({ className, ...props }: RadioGroupItemProps) => (
    <RadioPrimitive.Root
        data-slot='radio-group-item'
        className={cn(
            'peer relative flex size-4 shrink-0 items-center justify-center rounded-full border border-input transition-colors outline-none',
            'after:absolute after:-inset-x-3 after:-inset-y-2',
            'focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50',
            'data-checked:border-primary data-checked:bg-primary data-checked:text-primary-foreground',
            'aria-invalid:border-destructive aria-invalid:ring-3 aria-invalid:ring-destructive/20',
            // `data-disabled`, not `disabled:` -- see Checkbox.tsx.
            'data-disabled:cursor-not-allowed data-disabled:opacity-50',
            'dark:bg-input/30 dark:data-checked:bg-primary dark:aria-invalid:border-destructive/50 dark:aria-invalid:ring-destructive/40',
            className
        )}
        {...props}
    >
        <RadioPrimitive.Indicator
            data-slot='radio-group-indicator'
            className='flex items-center justify-center text-current'
        >
            <span className='size-1.5 rounded-full bg-current' />
        </RadioPrimitive.Indicator>
    </RadioPrimitive.Root>
)

export default RadioGroupItem
