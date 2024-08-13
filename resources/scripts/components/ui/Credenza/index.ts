import Credenza from './Credenza'
import CredenzaBody from './CredenzaBody'
import CredenzaClose from './CredenzaClose'
import CredenzaContent from './CredenzaContent'
import CredenzaDescription from './CredenzaDescription'
import CredenzaFooter from './CredenzaFooter'
import CredenzaHeader from './CredenzaHeader'
import CredenzaTitle from './CredenzaTitle'
import CredenzaTrigger from './CredenzaTrigger'


const desktop = '(min-width: 768px)'

interface BaseProps {
    children: React.ReactNode
}

interface CredenzaProps extends BaseProps {
    className?: string
    asChild?: true
}

export {
    desktop,
    type BaseProps,
    type CredenzaProps,
    Credenza,
    CredenzaTrigger,
    CredenzaClose,
    CredenzaContent,
    CredenzaDescription,
    CredenzaHeader,
    CredenzaTitle,
    CredenzaBody,
    CredenzaFooter,
}
