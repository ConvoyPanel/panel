import { Toggle as TogglePrimitive } from '@base-ui/react/toggle'
import { type VariantProps } from 'class-variance-authority'

import { cn } from '@/utils'

import toggleVariants from './Toggle.variants.ts'

export interface ToggleProps
    extends TogglePrimitive.Props,
        VariantProps<typeof toggleVariants> {}

const Toggle = ({ className, variant, size, ...props }: ToggleProps) => (
    <TogglePrimitive
        data-slot='toggle'
        className={cn(toggleVariants({ variant, size, className }))}
        {...props}
    />
)

export default Toggle
