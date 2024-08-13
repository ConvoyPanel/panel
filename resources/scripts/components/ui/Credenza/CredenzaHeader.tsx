import { useMediaQuery } from '@mantine/hooks'

import { CredenzaProps, desktop } from '@/components/ui/Credenza/index.ts'
import { DialogHeader } from '@/components/ui/Dialog'
import { DrawerHeader } from '@/components/ui/Drawer'


const CredenzaHeader = ({ className, children, ...props }: CredenzaProps) => {
    const isDesktop = useMediaQuery(desktop)
    const CredenzaHeader = isDesktop ? DialogHeader : DrawerHeader

    return (
        <CredenzaHeader className={className} {...props}>
            {children}
        </CredenzaHeader>
    )
}

export default CredenzaHeader
