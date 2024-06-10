import { Dialog, Transition } from '@headlessui/react'
import type { VariantProps } from 'class-variance-authority'
import {
    ComponentPropsWithoutRef,
    ElementType,
    ReactNode,
    useContext,
} from 'react'

import sheetVariants from '@/components/ui/Sheet/Sheet.variants.ts'
import { SheetContext } from '@/components/ui/SheetHeadless/sheet-provider.ts'


export type SheetContentProps<C extends ElementType> = {
    as?: C
    children?: ReactNode
} & VariantProps<typeof sheetVariants> &
    ComponentPropsWithoutRef<C>

const SheetContent = <C extends ElementType = 'div'>({
    as,
    className,
    children,
    ...props
}: SheetContentProps<C>) => {
    const Component = as || 'div'
    const { open, onOpenChange } = useContext(SheetContext)

    return (
        <Transition show={open}>
            <Dialog
                as={Component}
                className='relative z-50 focus:outline-none'
                onClose={onOpenChange}
            ></Dialog>
        </Transition>
    )
}
