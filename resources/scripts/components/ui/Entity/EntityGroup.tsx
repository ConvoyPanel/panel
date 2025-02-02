import { cn } from '@/utils'
import { ComponentPropsWithoutRef } from 'react'

interface Props extends ComponentPropsWithoutRef<'ul'> {}

const EntityGroup = ({ className, ...props }: Props) => {
    return (
        <ul
            className={cn(
                'flex flex-col divide-y divide-accent border border-accent',
                className
            )}
            {...props}
        />
    )
}

export default EntityGroup
