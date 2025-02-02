import { cn } from '@/utils'
import { ComponentPropsWithoutRef } from 'react'

import { entityVariants } from '@/components/ui/Entity/index.ts'

interface Props extends ComponentPropsWithoutRef<'li'> {}

const Entity = ({ className, ...props }: Props) => {
    return <li className={cn(entityVariants(), className)} {...props} />
}

export default Entity
