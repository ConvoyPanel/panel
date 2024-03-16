import { cn } from '@/utils'
import { ComponentProps } from 'react'

const ContentContainer = ({ className, ...props }: ComponentProps<'div'>) => {
    return (
        <div
            className={cn('w-full mx-auto my-16 space-y-16 max-w-7xl')}
            {...props}
        />
    )
}

export default ContentContainer
