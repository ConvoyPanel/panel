import { useMediaQuery } from '@mantine/hooks'

import { CredenzaProps, desktop } from '@/components/ui/Credenza/index.ts'
import { DialogTrigger } from '@/components/ui/Dialog'
import { DrawerTrigger } from '@/components/ui/Drawer'


const CredenzaTrigger = ({ className, children, ...props }: CredenzaProps) => {
    const isDesktop = useMediaQuery(desktop)
    const CredenzaTrigger = isDesktop ? DialogTrigger : DrawerTrigger

    return (
        <CredenzaTrigger className={className} {...props}>
            {children}
        </CredenzaTrigger>
    )
}

export default CredenzaTrigger
