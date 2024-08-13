import { useMediaQuery } from '@mantine/hooks'

import { CredenzaProps, desktop } from '@/components/ui/Credenza/index.ts'
import { DialogTitle } from '@/components/ui/Dialog'
import { DrawerTitle } from '@/components/ui/Drawer'


const CredenzaTitle = ({ className, children, ...props }: CredenzaProps) => {
    const isDesktop = useMediaQuery(desktop)
    const CredenzaTitle = isDesktop ? DialogTitle : DrawerTitle

    return (
        <CredenzaTitle className={className} {...props}>
            {children}
        </CredenzaTitle>
    )
}

export default CredenzaTitle
