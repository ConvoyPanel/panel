import { useMediaQuery } from '@mantine/hooks'

import { CredenzaProps, desktop } from '@/components/ui/Credenza/index.ts'
import { DialogContent } from '@/components/ui/Dialog'
import { DrawerContent } from '@/components/ui/Drawer'


const CredenzaContent = ({ className, children, ...props }: CredenzaProps) => {
    const isDesktop = useMediaQuery(desktop)
    const CredenzaContent = isDesktop ? DialogContent : DrawerContent

    return (
        <CredenzaContent className={className} {...props}>
            {children}
        </CredenzaContent>
    )
}

export default CredenzaContent
