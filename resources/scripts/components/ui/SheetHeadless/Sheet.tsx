import { ReactNode, useEffect, useState } from 'react'

import { SheetContext } from '@/components/ui/SheetHeadless/sheet-provider.ts'

interface Props {
    defaultOpen?: boolean
    open?: boolean
    onOpenChange?: (isOpen: boolean) => void
    children?: ReactNode
}

const Sheet = ({
    defaultOpen = false,
    open: controlledOpen,
    onOpenChange: controlledOnOpenChange,
    children,
}: Props) => {
    const [open, setOpen] = useState(defaultOpen)

    // Handle external control if provided
    useEffect(() => {
        if (controlledOpen !== undefined) {
            setOpen(controlledOpen)
        }
    }, [controlledOpen])

    const handleOpenChange = (isOpen: boolean) => {
        if (controlledOnOpenChange) {
            controlledOnOpenChange(isOpen)
        } else {
            setOpen(isOpen)
        }
    }

    return (
        <SheetContext.Provider
            value={{ open: open, onOpenChange: handleOpenChange }}
        >
            {children}
        </SheetContext.Provider>
    )
}

export default Sheet
