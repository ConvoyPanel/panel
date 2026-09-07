import { cn } from '@/utils'
import { ComponentPropsWithoutRef, ReactNode } from 'react'

interface PageToolbarProps extends ComponentPropsWithoutRef<'div'> {
    /** Buttons, menus and dialogs that act on the page. Right-aligned. */
    actions?: ReactNode
}

/**
 * The row of page-level controls that sits *under* a `Heading`, never beside
 * it. Deliberately the same shape as `DataTableToolbar` — filters (children)
 * left, actions right — so a page whose content is a table and a page whose
 * content is a card list put their buttons in the same place, at the same
 * height, on every screen.
 */
const PageToolbar = ({
    actions,
    className,
    children,
    ...props
}: PageToolbarProps) => (
    <div
        className={cn('flex flex-wrap items-center gap-2', className)}
        {...props}
    >
        <div className='flex min-w-0 flex-1 flex-wrap items-center gap-2'>
            {children}
        </div>
        <div className='ml-auto flex items-center gap-2'>{actions}</div>
    </div>
)

export default PageToolbar
