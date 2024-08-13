import { useMediaQuery } from '@mantine/hooks'

import { CredenzaProps, desktop } from '@/components/ui/Credenza/index.ts'
import { DialogDescription } from '@/components/ui/Dialog'
import { DrawerDescription } from '@/components/ui/Drawer'


const CredenzaDescription = ({
    className,
    children,
    ...props
}: CredenzaProps) => {
    const isDesktop = useMediaQuery(desktop)
    const CredenzaDescription = isDesktop
        ? DialogDescription
        : DrawerDescription

    return (
        <CredenzaDescription className={className} {...props}>
            {children}
        </CredenzaDescription>
    )
}

export default CredenzaDescription
