import { cn } from '@/utils'
import type { VariantProps } from 'class-variance-authority'
import { InputHTMLAttributes, forwardRef } from 'react'

import inputVariants from './Input.variants'

export interface InputProps
    extends
        InputHTMLAttributes<HTMLInputElement>,
        VariantProps<typeof inputVariants> {}

const Input = forwardRef<HTMLInputElement, InputProps>(
    ({ className, type, variant, ...props }, ref) => {
        return (
            <input
                type={type}
                className={cn(inputVariants({ variant }), className)}
                ref={ref}
                {...props}
            />
        )
    }
)
Input.displayName = 'Input'

export default Input
