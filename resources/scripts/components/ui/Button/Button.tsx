import useDelayedLoading from '@/hooks/use-delayed-loading.ts'
import { cn } from '@/utils'
import { Slot } from '@radix-ui/react-slot'
import type { VariantProps } from 'class-variance-authority'
import { ButtonHTMLAttributes, ReactNode, forwardRef } from 'react'

import Spinner from '@/components/ui/Spinner.tsx'

import buttonVariants from './Button.variants.ts'


export interface ButtonProps
    extends ButtonHTMLAttributes<HTMLButtonElement>,
        VariantProps<typeof buttonVariants> {
    icon?: ReactNode
    loading?: boolean
    asChild?: boolean
}

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
    (
        {
            className,
            variant,
            size,
            icon,
            disabled,
            loading,
            asChild = false,
            children,
            ...props
        },
        ref
    ) => {
        const Comp = asChild ? Slot : 'button'

        // Pass `loading` plainly from the call site; the timing lives here.
        // A spinner that comes and goes within a couple hundred milliseconds
        // reads as a glitch, so it is withheld until the work proves slow and
        // then held briefly so it cannot flash. `disabled` deliberately tracks
        // the raw flag, not this one: the press is acknowledged instantly and
        // can never fire twice while the spinner is being withheld.
        const showSpinner = useDelayedLoading(!!loading)

        return (
            <Comp
                className={cn(buttonVariants({ variant, size, className }))}
                disabled={disabled || loading}
                ref={ref}
                {...props}
            >
                {showSpinner ? <Spinner className={'size-4'} /> : icon}
                {children}
            </Comp>
        )
    }
)
Button.displayName = 'Button'

export default Button
