import { cn } from '@/utils'

import { CredenzaProps } from '@/components/ui/Credenza/index.ts'


const CredenzaBody = ({ className, children, ...props }: CredenzaProps) => {
    return (
        <div className={cn('px-4 md:px-0', className)} {...props}>
            {children}
        </div>
    )
}

export default CredenzaBody
