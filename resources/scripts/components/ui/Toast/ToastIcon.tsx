import {
    IconAlertOctagon,
    IconAlertTriangle,
    IconCircleCheck,
    IconInfoCircle,
} from '@tabler/icons-react'
import { ReactNode } from 'react'

import Spinner from '@/components/ui/Spinner.tsx'

// Keyed off the `type` passed to `toast.add()`. An unrecognised (or absent) type
// renders nothing, which is how a plain untyped toast stays icon-less.
const icons: Record<string, ReactNode> = {
    success: <IconCircleCheck aria-hidden={'true'} />,
    info: <IconInfoCircle aria-hidden={'true'} />,
    warning: <IconAlertTriangle aria-hidden={'true'} />,
    error: (
        <IconAlertOctagon aria-hidden={'true'} className={'text-destructive'} />
    ),
    loading: <Spinner aria-hidden={'true'} />,
}

const ToastIcon = ({ type }: { type: string | undefined }) => {
    const icon = type ? icons[type] : null

    if (!icon) {
        return null
    }

    return (
        <span
            data-slot={'toast-icon'}
            className={
                "shrink-0 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4"
            }
        >
            {icon}
        </span>
    )
}
ToastIcon.displayName = 'ToastIcon'

export default ToastIcon
