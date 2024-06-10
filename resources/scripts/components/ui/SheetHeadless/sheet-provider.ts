import { createContext } from 'react'

interface Context {
    open?: boolean
    onOpenChange?: (open: boolean) => void
}

export const SheetContext = createContext<Context>({})
