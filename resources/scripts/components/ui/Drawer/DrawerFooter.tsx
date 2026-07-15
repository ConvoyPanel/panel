import { cn } from '@/utils'
import { HTMLAttributes } from 'react'

const DrawerFooter = ({
    className,
    ...props
}: HTMLAttributes<HTMLDivElement>) => (
    <div
        data-slot={'drawer-footer'}
        // The drawer's take on nova's DialogFooter: same muted action bar, but
        // no negative margins — the drawer popup has no padding to cancel.
        className={cn(
            'mt-auto flex flex-col gap-2 border-t bg-muted/50 p-4',
            className
        )}
        {...props}
    />
)
DrawerFooter.displayName = 'DrawerFooter'

export default DrawerFooter
