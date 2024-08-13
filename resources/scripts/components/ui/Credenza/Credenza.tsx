import { useMediaQuery } from '@mantine/hooks'

import { BaseProps, desktop } from '@/components/ui/Credenza/index.ts'
import { Dialog } from '@/components/ui/Dialog'
import { Drawer } from '@/components/ui/Drawer'

interface RootCredenzaProps extends BaseProps {
    open?: boolean
    onOpenChange?: (open: boolean) => void
}

const Credenza = ({ children, ...props }: RootCredenzaProps) => {
    const isDesktop = useMediaQuery(desktop)
    const Credenza = isDesktop ? Dialog : Drawer

    return <Credenza {...props}>{children}</Credenza>
}

export default Credenza
