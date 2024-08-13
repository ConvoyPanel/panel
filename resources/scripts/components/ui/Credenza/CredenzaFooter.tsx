import { useMediaQuery } from '@mantine/hooks'

import { CredenzaProps, desktop } from '@/components/ui/Credenza/index.ts'
import { DialogFooter } from '@/components/ui/Dialog'
import { DrawerFooter } from '@/components/ui/Drawer'


const CredenzaFooter = ({ className, children, ...props }: CredenzaProps) => {
    const isDesktop = useMediaQuery(desktop)
    const CredenzaFooter = isDesktop ? DialogFooter : DrawerFooter

    return (
        <CredenzaFooter className={className} {...props}>
            {children}
        </CredenzaFooter>
    )
}

export default CredenzaFooter
