import { cn } from '@/utils'
import { Switch as SwitchPrimitive } from '@base-ui/react/switch'

export interface SwitchProps extends SwitchPrimitive.Root.Props {}

const Switch = ({ className, ...props }: SwitchProps) => (
    <SwitchPrimitive.Root
        data-slot='switch'
        className={cn(
            'peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border border-transparent bg-input transition-colors outline-none focus-visible:ring-3 focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 data-checked:bg-primary dark:bg-input/60',
            className
        )}
        {...props}
    >
        <SwitchPrimitive.Thumb
            data-slot='switch-thumb'
            className={cn(
                'pointer-events-none block size-4 translate-x-0.5 rounded-full bg-background shadow-sm ring-0 transition-transform data-checked:translate-x-[18px]'
            )}
        />
    </SwitchPrimitive.Root>
)

export default Switch
