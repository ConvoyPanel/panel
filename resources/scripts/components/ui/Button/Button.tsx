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
        return (
            <Comp
                className={cn(buttonVariants({ variant, size, className }))}
                disabled={disabled || loading}
                ref={ref}
                {...props}
            >
                {loading ? (
                    <Spinner
                        className={'animate-spin duration-700 mr-2 h-4 w-4'}
                    />
                ) : (
                    icon
                )}
                {children}
            </Comp>
        )
    }
)
Button.displayName = 'Button'

export default Button
