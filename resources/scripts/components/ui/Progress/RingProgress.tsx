import { cn } from '@/utils'
import { ComponentProps, useMemo } from 'react'

interface Props extends ComponentProps<'svg'> {
    value?: number | null
    thickness?: 'sm' | 'md' | 'lg' | 'xl' | null
}

const RingProgress = ({
    className,
    value,
    thickness: unparsedThickness,
    ...props
}: Props) => {
    const thickness = useMemo(() => {
        if (unparsedThickness === 'sm') return 3
        if (unparsedThickness === 'md') return 5
        if (unparsedThickness === 'lg') return 7
        if (unparsedThickness === 'xl') return 9

        return 3
    }, [unparsedThickness])

    return (
        <svg
            className={cn('rotate-[120deg] transform', className)}
            viewBox='0 0 40 40'
            {...props}
        >
            <circle
                cx='20'
                cy='20'
                r='15.9155'
                className='fill-none stroke-primary/20'
                strokeWidth={thickness}
            />
            <circle
                cx='20'
                cy='20'
                r='15.9155'
                className='fill-none stroke-primary transition-all duration-700'
                strokeWidth={thickness}
                strokeLinecap='round'
                strokeDasharray={`${value || 0}, 100`}
            />
        </svg>
    )
}

export default RingProgress
