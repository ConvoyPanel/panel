import { useMediaQuery } from '@mantine/hooks'

import { CredenzaProps, desktop } from '@/components/ui/Credenza/index.ts'
import { DialogClose } from '@/components/ui/Dialog'
import { DrawerClose } from '@/components/ui/Drawer'


const CredenzaClose = ({ className, children, ...props }: CredenzaProps) => {
    const isDesktop = useMediaQuery(desktop)
    const CredenzaClose = isDesktop ? DialogClose : DrawerClose

    return (
        <CredenzaClose className={className} {...props}>
            {children}
        </CredenzaClose>
    )
}

export default CredenzaClose
