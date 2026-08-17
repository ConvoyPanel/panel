import useDelayedLoading from '@/hooks/use-delayed-loading.ts'
import { cn } from '@/utils'
import { Slot } from '@radix-ui/react-slot'
import type { VariantProps } from 'class-variance-authority'
import {
    ButtonHTMLAttributes,
    ReactElement,
    ReactNode,
    cloneElement,
    forwardRef,
    isValidElement,
} from 'react'

import Spinner from '@/components/ui/Spinner.tsx'

import buttonVariants from './Button.variants.ts'

export interface ButtonProps
    extends
        ButtonHTMLAttributes<HTMLButtonElement>,
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
        const adornment = showSpinner ? <Spinner className={'size-4'} /> : icon

        /*
         * Under `asChild` the adornment has to go *inside* the child, not
         * beside it. Slot runs `React.Children.only`, so handing it
         * `{adornment}{children}` is two children even when the adornment is
         * undefined -- an array, not an element -- and every `<Button asChild>`
         * in the app threw "expected to receive a single React element child"
         * before it could render. Cloning keeps the icon and the spinner
         * working on a link the way they work on a button.
         */
        const content =
            asChild && isValidElement<{ children?: ReactNode }>(children) ? (
                cloneElement(
                    children as ReactElement<{ children?: ReactNode }>,
                    undefined,
                    <>
                        {adornment}
                        {children.props.children}
                    </>
                )
            ) : (
                <>
                    {adornment}
                    {children}
                </>
            )

        return (
            <Comp
                // nova keys its group/joining selectors off `[data-slot]`
                // (see ButtonGroup): without this they compile and then match
                // nothing, which is silent rather than loud.
                data-slot={'button'}
                className={cn(buttonVariants({ variant, size, className }))}
                disabled={disabled || loading}
                ref={ref}
                {...props}
            >
                {content}
            </Comp>
        )
    }
)
Button.displayName = 'Button'

export default Button
