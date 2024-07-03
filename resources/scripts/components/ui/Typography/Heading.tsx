import { cn } from '@/utils'
import { ComponentProps } from 'react'

interface Props extends ComponentProps<'h1'> {}

const Heading = ({ className, ...props }: Props) => {
    return <h1 className={cn('text-3xl font-semibold', className)} {...props} />
}

export default Heading
