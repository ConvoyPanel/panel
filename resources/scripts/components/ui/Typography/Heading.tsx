import { cn } from '@/utils'
import { ComponentPropsWithoutRef, ElementType } from 'react'

type HeadingProps<T extends ElementType> = {
    as?: T
} & ComponentPropsWithoutRef<T>

const Heading = <T extends ElementType = 'h1'>({
    as,
    className,
    ...props
}: HeadingProps<T>) => {
    const Component = as || 'h1'
    return (
        <Component
            className={cn('text-2xl font-semibold sm:text-3xl', className)}
            {...props}
        />
    )
}

export default Heading
