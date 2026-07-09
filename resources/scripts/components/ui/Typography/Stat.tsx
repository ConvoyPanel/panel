import { cn } from '@/utils'
import { ComponentPropsWithoutRef, ElementType, ReactNode } from 'react'

type StatLabelProps<T extends ElementType> = {
    as?: T
} & ComponentPropsWithoutRef<T>

const StatLabel = <T extends ElementType = 'div'>({
    as,
    className,
    ...props
}: StatLabelProps<T>) => {
    const Component = as || 'div'

    return <Component className={cn('text-label', className)} {...props} />
}

interface StatProps extends ComponentPropsWithoutRef<'div'> {
    label: ReactNode
    value: ReactNode
    labelClassName?: string
    valueClassName?: string
}

const Stat = ({
    label,
    value,
    labelClassName,
    valueClassName,
    className,
    ...props
}: StatProps) => (
    <div className={className} {...props}>
        <StatLabel className={cn('text-xs', labelClassName)}>{label}</StatLabel>
        <div
            className={cn(
                'mt-0.5 text-xl font-semibold tabular-nums',
                valueClassName
            )}
        >
            {value}
        </div>
    </div>
)

export { StatLabel }
export default Stat
